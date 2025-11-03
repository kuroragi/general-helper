<?php
namespace Kuroragi\GeneralHelper\ActivityLog\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use ZipArchive;
use Carbon\Carbon;

class RollActivityLogs extends Command
{
    protected $signature = 'kuroragi:roll-activity-logs';
    protected $description = 'Roll weekly activity logs into an archive and remove raw daily logs of that week.';

    public function handle()
    {
        $path = config('kuroragi.activity_log_path', storage_path('logs/activity'));
        if (!File::isDirectory($path)) {
            $this->info("No activity log directory exists at {$path}");
            return 0;
        }

        // Get previous week range (Mon-Sun) relative to now (assuming running Monday 01:00)
        $now = Carbon::now();
        $prevWeekStart = $now->copy()->startOfWeek()->subWeek(); // Monday last week
        $prevWeekEnd = $prevWeekStart->copy()->endOfWeek(); // Sunday

        $files = collect(File::files($path))
            ->filter(function($f) use ($prevWeekStart, $prevWeekEnd) {
                $name = $f->getFilename();
                // expecting activity-YYYY-MM-DD.log
                if (preg_match('/activity-(\d{4}-\d{2}-\d{2})\.log$/', $name, $m)) {
                    $d = Carbon::createFromFormat('Y-m-d', $m[1]);
                    return $d->between($prevWeekStart, $prevWeekEnd);
                }
                return false;
            });

        if ($files->isEmpty()) {
            $this->info('No logs to roll for previous week.');
            return 0;
        }

        $zipName = "activity-week-{$prevWeekStart->format('Ymd')}-to-{$prevWeekEnd->format('Ymd')}.zip";
        $zipPath = $path . DIRECTORY_SEPARATOR . $zipName;

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
            $this->error("Cannot create archive at {$zipPath}");
            return 1;
        }

        foreach ($files as $f) {
            $zip->addFile($f->getRealPath(), $f->getFilename());
        }
        $zip->close();

        // delete original daily logs for the week
        foreach ($files as $f) {
            File::delete($f->getRealPath());
        }

        $this->info("Rolled files into {$zipName}");
        return 0;
    }
}
