<?php
namespace Kuroragi\GeneralHelper\ActivityLog;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ActivityLogger
{
    protected string $path;

    public function __construct(string $path)
    {
        $this->path = rtrim($path, DIRECTORY_SEPARATOR);
        if (!File::isDirectory($this->path)) {
            File::makeDirectory($this->path, 0755, true);
        }
    }

    public function log(array $data): bool
    {
        // ensure minimum fields
        $entry = array_merge([
            'time' => now()->toDateTimeString(),
            'level' => $data['level'] ?? 'info',
            'category' => $data['category'] ?? 'general',
            'message' => $data['message'] ?? '',
            'meta' => $data['meta'] ?? null,
        ], $data);

        $date = now()->format('Y-m-d');
        $file = $this->path . DIRECTORY_SEPARATOR . "activity-{$date}.log";
        $line = json_encode($entry, JSON_UNESCAPED_UNICODE) . PHP_EOL;
        return (bool) file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }

    // helper to log common actions: transaction, created, updated, deleted, etc.
    public function transaction($message, $meta = [], $category = 'transaction')
    {
        return $this->log([
            'level' => 'info',
            'category' => $category,
            'message' => $message,
            'meta' => $meta,
        ]);
    }
}
