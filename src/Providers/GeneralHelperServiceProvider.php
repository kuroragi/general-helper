<?php
namespace Kuroragi\GeneralHelper\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Console\Scheduling\Schedule;
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

        // schedule rolling weekly at configured time
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            $rollDay = config('kuroragi.roll_day', 'monday');
            $rollTime = config('kuroragi.roll_time', '01:00');
            // schedule weekly on rollDay at rollTime
            $schedule->command('kuroragi:roll-activity-logs')
                     ->weeklyOn($this->dayOfWeekNumber($rollDay), explode(':', $rollTime)[0], explode(':', $rollTime)[1]);
        });
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
}
