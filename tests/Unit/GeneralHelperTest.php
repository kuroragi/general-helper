<?php

namespace Kuroragi\GeneralHelper\Tests\Unit;

use Kuroragi\GeneralHelper\Tests\TestCase;
use Kuroragi\GeneralHelper\GeneralHelper;

class GeneralHelperTest extends TestCase
{
    public function test_get_slug_converts_text_to_slug(): void
    {
        $this->assertSame('halo-dunia', GeneralHelper::getSlug('Halo Dunia!'));
        $this->assertSame('laravel-10', GeneralHelper::getSlug('Laravel 10'));
        $this->assertSame('hello-world', GeneralHelper::getSlug('  Hello   World  '));
    }

    public function test_get_slug_handles_special_characters(): void
    {
        $this->assertSame('aplikasi-erp', GeneralHelper::getSlug('Aplikasi ERP!@#'));
    }

    public function test_convert_date_to_indo_returns_formatted_string(): void
    {
        // Force locale to Indonesian
        \Carbon\Carbon::setLocale('id');
        $result = GeneralHelper::convertDateToIndo('2025-11-02');
        $this->assertStringContainsString('2025', $result);
        $this->assertStringContainsString('2', $result);
    }

    public function test_convert_date_to_indo_short_returns_string(): void
    {
        $result = GeneralHelper::convertDateToIndoShort('2025-11-02');
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function test_get_terbilang_returns_string_for_integer(): void
    {
        $result = GeneralHelper::getTerbilang(5);
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function test_get_indo_date_returns_string(): void
    {
        $result = GeneralHelper::getIndoDate('2025-11-02');
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function test_get_indo_date_terbilang_contains_date_and_terbilang(): void
    {
        $result = GeneralHelper::getIndoDateTerbilang('2025-11-02');
        $this->assertStringContainsString('—', $result);
    }
}
