<?php

namespace Tests\Unit;

use App\Support\SchoolTerm;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class SchoolTermTest extends TestCase
{
    public function test_resolves_terms_from_site_calendar(): void
    {
        $this->assertSame(SchoolTerm::FIRST, SchoolTerm::current(Carbon::parse('2026-08-15')));
        $this->assertSame(SchoolTerm::FIRST, SchoolTerm::current(Carbon::parse('2026-01-07')));
        $this->assertSame(SchoolTerm::FIRST, SchoolTerm::current(Carbon::parse('2026-01-12')));
        $this->assertSame(SchoolTerm::SECOND, SchoolTerm::current(Carbon::parse('2026-01-15')));
        $this->assertSame(SchoolTerm::SECOND, SchoolTerm::current(Carbon::parse('2026-05-28')));
        $this->assertSame(SchoolTerm::SUMMER, SchoolTerm::current(Carbon::parse('2026-06-01')));
        $this->assertSame(SchoolTerm::SUMMER, SchoolTerm::current(Carbon::parse('2026-07-20')));
    }
}
