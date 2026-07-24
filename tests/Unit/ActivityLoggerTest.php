<?php

namespace Kuroragi\GeneralHelper\Tests\Unit;

use Kuroragi\GeneralHelper\Tests\TestCase;
use Kuroragi\GeneralHelper\ActivityLog\ActivityLogger;
use Illuminate\Support\Facades\File;

class ActivityLoggerTest extends TestCase
{
    protected string $logPath;
    protected ActivityLogger $logger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->logPath = sys_get_temp_dir() . '/kuroragi_test_logs_' . uniqid();
        $this->logger = new ActivityLogger($this->logPath);
    }

    protected function tearDown(): void
    {
        // Clean up temp log directory
        if (is_dir($this->logPath)) {
            File::deleteDirectory($this->logPath);
        }
        parent::tearDown();
    }

    public function test_log_creates_directory_if_not_exists(): void
    {
        $this->assertDirectoryExists($this->logPath);
    }

    public function test_log_writes_entry_to_daily_file(): void
    {
        $result = $this->logger->log([
            'level'   => 'info',
            'message' => 'test log entry',
        ]);

        $this->assertTrue($result);

        $date = now()->format('Y-m-d');
        $file = $this->logPath . "/activity-{$date}.log";
        $this->assertFileExists($file);

        $content = file_get_contents($file);
        $this->assertStringContainsString('test log entry', $content);
    }

    public function test_log_entry_is_valid_json(): void
    {
        $this->logger->log([
            'level'    => 'info',
            'category' => 'test',
            'message'  => 'json check',
            'meta'     => ['key' => 'value'],
        ]);

        $date = now()->format('Y-m-d');
        $file = $this->logPath . "/activity-{$date}.log";
        $line = trim(file_get_contents($file));

        $decoded = json_decode($line, true);
        $this->assertIsArray($decoded);
        $this->assertSame('json check', $decoded['message']);
        $this->assertSame('test', $decoded['category']);
        $this->assertArrayHasKey('time', $decoded);
    }

    public function test_transaction_helper_writes_info_level(): void
    {
        $this->logger->transaction('Purchase Order dibuat', ['po_id' => 101]);

        $date = now()->format('Y-m-d');
        $file = $this->logPath . "/activity-{$date}.log";
        $decoded = json_decode(trim(file_get_contents($file)), true);

        $this->assertSame('info', $decoded['level']);
        $this->assertSame('transaction', $decoded['category']);
        $this->assertSame(101, $decoded['meta']['po_id']);
    }

    public function test_multiple_logs_append_to_same_file(): void
    {
        $this->logger->log(['message' => 'first']);
        $this->logger->log(['message' => 'second']);

        $date = now()->format('Y-m-d');
        $file = $this->logPath . "/activity-{$date}.log";
        $lines = array_filter(explode("\n", trim(file_get_contents($file))));

        $this->assertCount(2, $lines);
    }
}
