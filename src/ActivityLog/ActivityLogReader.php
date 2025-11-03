<?php
namespace Kuroragi\GeneralHelper\ActivityLog;

use Illuminate\Support\Facades\File;
use Carbon\Carbon;
use ZipArchive;

class ActivityLogReader
{
    protected string $path;
    protected int $defaultLimit;

    public function __construct(string $path, int $defaultLimit = 50)
    {
        $this->path = rtrim($path, DIRECTORY_SEPARATOR);
        $this->defaultLimit = $defaultLimit;
    }

    /**
     * Read logs.
     *
     * @param int|null $limit if null and there's keyword/category => read all
     * @param string|null $keyword
     * @param string|null $category
     * @param string|null $start Y-m-d
     * @param string|null $end Y-m-d
     * @return array lines decoded as arrays
     */
    public function read(?int $limit = null, ?string $keyword = null, ?string $category = null, ?string $start = null, ?string $end = null): array
    {
        $limit = $limit ?? $this->defaultLimit;

        $startDate = $start ? Carbon::createFromFormat('Y-m-d', $start)->startOfDay() : null;
        $endDate = $end ? Carbon::createFromFormat('Y-m-d', $end)->endOfDay() : null;

        $isSearchMode = (bool) ($keyword || $category || $startDate || $endDate);

        // [CHANGE] Normal mode: incremental backfill from today backwards until limit reached
        if (!$isSearchMode) {
            return $this->readLatestIncremental($limit);
        }

        // [CHANGE] Search/Range mode: read all relevant files (including archives) and filter
        $files = $this->gatherFiles($startDate, $endDate);

        $results = [];

        foreach ($files as $file) {
            if (str_ends_with($file, '.zip')) {
                $results = array_merge($results, $this->readZip($file, $keyword, $category, $startDate, $endDate));
            } else {
                $results = array_merge($results, $this->readLogFile($file, $keyword, $category, $startDate, $endDate));
            }
        }

        // sort by time desc
        usort($results, function($a, $b){
            return strtotime($b['time'] ?? 0) <=> strtotime($a['time'] ?? 0);
        });

        return $results;
    }

    // [CHANGE] New helper: incremental read starting from today backward until limit reached.
    protected function readLatestIncremental(int $limit): array
    {
        $results = [];
        if (!File::exists($this->path)) {
            return [];
        }

        // Build a map of available daily log files keyed by date string for quick lookup
        $availableDaily = [];
        $files = collect(File::files($this->path))
            ->map(fn($f) => $f->getRealPath())
            ->toArray();

        foreach ($files as $fpath) {
            $name = basename($fpath);
            if (preg_match('/^activity-(\d{4}-\d{2}-\d{2})\.log$/', $name, $m)) {
                $availableDaily[$m[1]] = $fpath;
            }
        }

        // Today's date and iterate backwards day by day
        $day = Carbon::now()->startOfDay();
        $earliestDate = null;
        if (!empty($availableDaily)) {
            $dates = array_keys($availableDaily);
            sort($dates); // ascending
            $earliestDate = Carbon::createFromFormat('Y-m-d', $dates[0])->startOfDay();
        }

        // First, read daily logs from today backwards
        while (true) {
            $dateKey = $day->format('Y-m-d');
            if (isset($availableDaily[$dateKey])) {
                // read this day's file in reverse (newest first)
                $rows = $this->readLogFileReverse($availableDaily[$dateKey], null, null, null, null, $limit - count($results));
                if (!empty($rows)) {
                    $results = array_merge($results, $rows);
                    if (count($results) >= $limit) {
                        return array_slice($results, 0, $limit);
                    }
                }
            }

            // stop if we've reached earliest available date
            if ($earliestDate && $day->lte($earliestDate)) {
                break;
            }

            // Move to previous day
            $day->subDay();

            // safety break if we've gone too far (no files)
            if ($day->diffInDays(Carbon::now()) > 365 * 5) {
                // avoid infinite loop: stop after 5 years back
                break;
            }
        }

        // If still not enough results, read zipped archives (newest archives first)
        // Gather zip files, sort by their contained end date desc
        $zipFiles = collect(File::files($this->path))
            ->filter(fn($f) => str_ends_with($f->getFilename(), '.zip'))
            ->map(fn($f) => $f->getRealPath())
            ->toArray();

        // parse zip name to get end date, sort by end date desc
        usort($zipFiles, function($a, $b) {
            $aName = basename($a);
            $bName = basename($b);
            preg_match('/activity-week-(\d{8})-to-(\d{8})\.zip$/', $aName, $am);
            preg_match('/activity-week-(\d{8})-to-(\d{8})\.zip$/', $bName, $bm);
            $aDate = $am[2] ?? '19700101';
            $bDate = $bm[2] ?? '19700101';
            return strcmp($bDate, $aDate);
        });

        foreach ($zipFiles as $zip) {
            $rows = $this->readZip($zip, null, null, null, null);
            if (!empty($rows)) {
                // rows returned unsorted possibly; ensure sorting desc by time
                usort($rows, function($a, $b){
                    return strtotime($b['time'] ?? 0) <=> strtotime($a['time'] ?? 0);
                });
                foreach ($rows as $r) {
                    $results[] = $r;
                    if (count($results) >= $limit) {
                        return array_slice($results, 0, $limit);
                    }
                }
            }
        }

        // final slice to ensure limit
        return array_slice($results, 0, $limit);
    }

    // [CHANGE] Read a day's log file but return newest-first up to $limit (if provided)
    protected function readLogFileReverse($file, $keyword, $category, $startDate, $endDate, $limit = null)
    {
        $out = [];
        if (!is_file($file)) return $out;

        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (empty($lines)) return $out;

        // lines are assumed chronological (oldest -> newest). Reverse to process newest first
        $lines = array_reverse($lines);

        foreach ($lines as $line) {
            $decoded = json_decode($line, true);
            if (!$decoded) continue;

            if ($this->matchesFilters($decoded, $keyword, $category, $startDate, $endDate)) {
                $out[] = $decoded;
                if ($limit !== null && count($out) >= $limit) {
                    break;
                }
            }
        }

        return $out;
    }

    protected function readLogFile($file, $keyword, $category, $startDate, $endDate)
    {
        $out = [];
        if (!is_file($file)) return $out;

        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $decoded = json_decode($line, true);
            if (!$decoded) continue;

            if ($this->matchesFilters($decoded, $keyword, $category, $startDate, $endDate)) {
                $out[] = $decoded;
            }
        }

        return $out;
    }

    protected function readZip($zipPath, $keyword, $category, $startDate, $endDate)
    {
        $out = [];
        $zip = new ZipArchive();
        if ($zip->open($zipPath) === true) {
            // read every file in zip
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                $name = $stat['name'];
                // We assume entries are text files containing newline-separated JSON lines
                $content = $zip->getFromIndex($i);
                if ($content === false) continue;
                $lines = explode(PHP_EOL, $content);
                foreach ($lines as $line) {
                    if (trim($line) === '') continue;
                    $decoded = json_decode($line, true);
                    if (!$decoded) continue;
                    if ($this->matchesFilters($decoded, $keyword, $category, $startDate, $endDate)) {
                        $out[] = $decoded;
                    }
                }
            }
            $zip->close();
        }
        // ensure results are sorted by time desc before return
        usort($out, function($a, $b){
            return strtotime($b['time'] ?? 0) <=> strtotime($a['time'] ?? 0);
        });
        return $out;
    }

    /**
     * Gather files in path. If startDate or endDate provided, filter to those ranges (including zip ranges).
     * Otherwise return files sorted by modified time desc (newest first).
     */
    protected function gatherFiles($startDate = null, $endDate = null)
    {
        if (!File::exists($this->path)) {
            return [];
        }
        $all = collect(File::files($this->path))
            ->map(fn($f) => $f->getRealPath())
            ->toArray();

        // If range provided, filter files that overlap the date range (for logs and zips)
        if ($startDate || $endDate) {
            return array_filter($all, function($path) use ($startDate, $endDate) {
                $name = basename($path);
                if (preg_match('/activity-(\d{4}-\d{2}-\d{2})\.log$/', $name, $m)) {
                    $d = Carbon::createFromFormat('Y-m-d', $m[1])->startOfDay();
                    if ($startDate && $d->lt($startDate)) return false;
                    if ($endDate && $d->gt($endDate)) return false;
                    return true;
                } elseif (str_ends_with($name, '.zip')) {
                    if (preg_match('/activity-week-(\d{8})-to-(\d{8})\.zip$/', $name, $m2)) {
                        $s = Carbon::createFromFormat('Ymd', $m2[1])->startOfDay();
                        $e = Carbon::createFromFormat('Ymd', $m2[2])->endOfDay();
                        // overlap check
                        if ($startDate && $e->lt($startDate)) return false;
                        if ($endDate && $s->gt($endDate)) return false;
                        return true;
                    }
                }
                return false;
            });
        }

        // No range: return files ordered by date/newest first:
        // prioritize daily logs by filename date descending, then zip archives by their end date descending
        $daily = [];
        $zips = [];
        foreach ($all as $p) {
            $n = basename($p);
            if (preg_match('/activity-(\d{4}-\d{2}-\d{2})\.log$/', $n, $m)) {
                $daily[$m[1]] = $p;
            } elseif (preg_match('/activity-week-(\d{8})-to-(\d{8})\.zip$/', $n, $m2)) {
                // store keyed by end date for sorting
                $zips[$m2[2]] = $p;
            }
        }

        // sort daily by date desc
        krsort($daily);
        // sort zips by end-date desc
        krsort($zips);

        // merge: daily first (newest -> older), then zips (newest -> older)
        return array_merge(array_values($daily), array_values($zips));
    }

    protected function matchesFilters($entry, $keyword, $category, $startDate, $endDate)
    {
        if ($startDate || $endDate) {
            $t = isset($entry['time']) ? Carbon::parse($entry['time']) : null;
            if ($t) {
                if ($startDate && $t->lt($startDate)) return false;
                if ($endDate && $t->gt($endDate)) return false;
            }
        }

        if ($category) {
            if (isset($entry['category']) && stripos($entry['category'], $category) === false) {
                return false;
            }
        }

        if ($keyword) {
            $hay = json_encode($entry);
            if (stripos($hay, $keyword) === false) return false;
        }

        return true;
    }
}
