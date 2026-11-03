<?php

namespace Tests\Feature;

use App\Models\MonthlyAnalyticsSnapshot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonthlyAnalyticsCloseTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_dean_can_close_previous_month_once_without_changing_live_records(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 3)->setTime(12, 0));
        $faculty = User::where('username', 'faculty')->first();
        if ($faculty) {
            $this->actingAs($faculty)->post(route('dean.analytics.close-month'))->assertForbidden();
        }
        $dean = User::where('username', 'dean')->firstOrFail();
        $userCount = User::count();
        $this->actingAs($dean)->post(route('dean.analytics.close-month'))->assertRedirect();
        $snapshot = MonthlyAnalyticsSnapshot::sole();
        $this->assertSame('2026-10-01', $snapshot->period_start->toDateString());
        $this->assertArrayHasKey('Recorded actions', $snapshot->metrics);
        $this->assertSame($userCount, User::count());
        $this->actingAs($dean)->post(route('dean.analytics.close-month'))->assertStatus(422);
        $this->assertSame(1, MonthlyAnalyticsSnapshot::count());
    }
}
