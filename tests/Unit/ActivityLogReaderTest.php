<?php

namespace Kuroragi\GeneralHelper\Tests\Unit;

use Kuroragi\GeneralHelper\Tests\TestCase;
use Kuroragi\GeneralHelper\ActivityLog\ActivityLogger;
use Kuroragi\GeneralHelper\ActivityLog\ActivityLogReader;
use Illuminate\Support\Facades\File;

class ActivityLogReaderTest extends TestCase
{
    protected string $logPath;
    protected ActivityLogger $logger;
    protected ActivityLogReader $reader;

    protected function setUp(): void
    {
        parent::setUp();
        $this->logPath = sys_get_temp_dir() . '/kuroragi_reader_test_' . uniqid();
        $this->logger = new ActivityLogger($this->logPath);
        $this->reader = new ActivityLogReader($this->logPath, 50);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->logPath)) {
            File::deleteDirectory($this->logPath);
        }
        parent::tearDown();
    }

    public function test_read_returns_array(): void
    {
        $this->logger->log(['message' => 'entry one']);
        $results = $this->reader->read();
        $this->assertIsArray($results);
    }

    public function test_read_returns_logged_entries(): void
    {
        $this->logger->log(['message' => 'entry alpha']);
        $this->logger->log(['message' => 'entry beta']);

        $results = $this->reader->read(10);
        $messages = array_column($results, 'message');

        $this->assertContains('entry alpha', $messages);
        $this->assertContains('entry beta', $messages);
    }

    public function test_read_respects_limit(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            $this->logger->log(['message' => "entry {$i}"]);
        }

        $results = $this->reader->read(3);
        $this->assertLessThanOrEqual(3, count($results));
    }

    public function test_read_with_keyword_filters_results(): void
    {
        $this->logger->log(['message' => 'purchase order created', 'category' => 'transaction']);
        $this->logger->log(['message' => 'user logged in', 'category' => 'auth']);

        $results = $this->reader->read(null, 'purchase');
        $messages = array_column($results, 'message');

        $this->assertContains('purchase order created', $messages);
        $this->assertNotContains('user logged in', $messages);
    }

    public function test_read_with_category_filters_results(): void
    {
        $this->logger->log(['message' => 'tx1', 'category' => 'transaction']);
        $this->logger->log(['message' => 'auth1', 'category' => 'auth']);

        $results = $this->reader->read(null, null, 'auth');
        $categories = array_column($results, 'category');

        foreach ($categories as $cat) {
            $this->assertSame('auth', $cat);
        }
    }

    public function test_read_returns_empty_array_for_no_logs(): void
    {
        $results = $this->reader->read();
        $this->assertSame([], $results);
    }
}
