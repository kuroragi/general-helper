<?php
namespace Kuroragi\GeneralHelper\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Kuroragi\GeneralHelper\ActivityLog\ActivityLogger;
use Kuroragi\GeneralHelper\ActivityLog\Commands\RollActivityLogs;
use Kuroragi\GeneralHelper\Macros\EloquentMacros;
use Kuroragi\GeneralHelper\Macros\BlueprintMacros;

class GeneralHelperServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/kuroragi.php', 'kuroragi');

        $this->app->singleton('kuroragi.activity', function ($app) {
            return new ActivityLogger($app['config']->get('kuroragi.activity_log_path'));
        });
    }

    public function boot()
    {
        // publish config
        $this->publishes([
            __DIR__ . '/../../config/kuroragi.php' => config_path('kuroragi.php'),
        ], 'config');

        // register console command
        if ($this->app->runningInConsole()) {
            $this->commands([
                RollActivityLogs::class,
            ]);
        }

        // register macros
        EloquentMacros::register();

        // schedule rolling weekly at configured time (Laravel 12 compatible)
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $rollDay = config('kuroragi.roll_day', 'monday');
            $rollTime = config('kuroragi.roll_time', '01:00');
            [$hour, $minute] = explode(':', $rollTime);

            $schedule->command('kuroragi:roll-activity-logs')
                     ->weeklyOn($this->dayOfWeekNumber($rollDay), $hour, $minute);
        });

        // register authorization exception handler
        $this->registerAuthorizationExceptionHandler();
    }

    protected function dayOfWeekNumber(string $day)
    {
        $map = [
            'sunday' => 0,
            'monday' => 1,
            'tuesday' => 2,
            'wednesday' => 3,
            'thursday' => 4,
            'friday' => 5,
            'saturday' => 6,
        ];
        return $map[strtolower($day)] ?? 1;
    }

    /**
     * Register authorization exception handler for 403 errors.
     * Redirects unauthorized users to configured route with flash message.
     */
    protected function registerAuthorizationExceptionHandler()
    {
        if (!config('kuroragi.authorization_exception.enabled', false)) {
            return;
        }

        $this->app->make(ExceptionHandler::class)->renderable(function (AuthorizationException $e, $request) {
            $redirectType = config('kuroragi.authorization_exception.redirect_type', 'route');
            $redirectTo = config('kuroragi.authorization_exception.redirect_to', 'dashboard');
            $sessionKey = config('kuroragi.authorization_exception.session_key', 'no_access');
            $message = config('kuroragi.authorization_exception.message', 'Kamu tidak memiliki hak akses ke halaman tersebut.');
            $handleJson = config('kuroragi.authorization_exception.json_response', true);

            // Determine redirect URL based on type
            $redirectUrl = $this->getRedirectUrl($redirectType, $redirectTo);

            // Handle JSON/AJAX requests (e.g., Livewire, API)
            if ($handleJson && $request->expectsJson()) {
                return response()->json([
                    'redirect' => $redirectUrl,
                    'message' => $message
                ], 403);
            }

            // Handle regular HTTP requests
            return redirect($redirectUrl)
                ->with($sessionKey, $message);
        });
    }

    /**
     * Get redirect URL based on configured type.
     *
     * @param string $type 'route', 'url', 'back', or 'home'
     * @param string|null $value route name or URL
     * @return string
     */
    protected function getRedirectUrl(string $type, ?string $value): string
    {
        return match($type) {
            'route' => route($value ?? 'dashboard'),
            'url' => $value ?? '/',
            'back' => url()->previous(),
            'home' => url('/'),
            default => route('dashboard'),
        };
    }
}
