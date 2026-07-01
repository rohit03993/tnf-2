<?php

namespace Tests\Unit;

use App\Support\NewsDate;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class NewsDateTest extends TestCase
{
    public function test_formats_published_time_in_india_timezone(): void
    {
        $utc = Carbon::parse('2026-07-01 07:16:00', 'UTC');

        $this->assertSame('1 Jul 2026 · 12:46 PM', NewsDate::formatDateTime($utc));
        $this->assertSame('1 Jul 2026', NewsDate::formatDate($utc));
    }
}
