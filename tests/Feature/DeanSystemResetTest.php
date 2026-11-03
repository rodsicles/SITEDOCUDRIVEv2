<?php

namespace Tests\Feature;

use App\Models\DashboardLog;
use App\Models\SystemResetEvent;
use App\Models\User;
use App\Services\SystemResetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeanSystemResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_dean_can_restart_monthly_summary_without_deleting_records(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 3)->setTime(12, 0));
        $dean = User::where('username', 'dean')->firstOrFail();
        DashboardLog::create(['user_id' => $dean->id, 'activity' => 'Before reset', 'activity_type' => 'test']);

        $this->actingAs($dean)->post(route('dean.analytics.reset-this-month'))->assertRedirect(route('dean.analytics'));

        $reset = SystemResetEvent::where('scope', 'analytics_month')->firstOrFail();
        $this->assertSame(1, $reset->closed_metrics['Recorded actions']);
        $this->assertSame(1, DashboardLog::count());
        $this->assertSame(0, app(SystemResetService::class)->monthMetrics(now(), $reset)['Recorded actions']);

        $this->travel(2)->seconds();
        DashboardLog::create(['user_id' => $dean->id, 'activity' => 'After reset', 'activity_type' => 'test']);
        $this->assertSame(1, app(SystemResetService::class)->monthMetrics(now(), $reset)['Recorded actions']);
    }

    public function test_activity_log_reset_hides_older_rows_but_keeps_dean_history(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 3)->setTime(12, 0));
        $dean = User::where('username', 'dean')->firstOrFail();
        DashboardLog::create(['user_id' => $dean->id, 'activity' => 'Before reset', 'activity_type' => 'test']);

        $this->actingAs($dean)->get(route('dean.dashboard'))->assertOk()->assertSee('Before reset');

        $this->actingAs($dean)->post(route('dean.audit-trail.reset'))->assertRedirect(route('dean.audit-trail'));
        $this->assertSame(2, DashboardLog::count());
        $this->get(route('dean.audit-trail'))->assertOk()->assertDontSee('Before reset');
        $this->get(route('dean.audit-trail'))->assertOk()->assertSee('Reset Activity Log view');
        $this->get(route('dean.audit-trail', ['history' => 1]))->assertOk()->assertSee('Before reset');
        $this->get(route('dean.dashboard'))->assertOk()->assertDontSee('Before reset')->assertSee('No recent activity');

        $this->travel(2)->seconds();
        DashboardLog::create(['user_id' => $dean->id, 'activity' => 'After reset', 'activity_type' => 'test']);
        \Illuminate\Support\Facades\Cache::forget('dean_command_center_'.$dean->id);
        $this->get(route('dean.dashboard'))->assertOk()->assertSee('After reset');
    }

    public function test_non_dean_cannot_reset_either_view(): void
    {
        $coordinator = User::where('username', 'coordinator')->first();
        if (!$coordinator) $this->markTestSkipped('No coordinator fixture exists.');

        $this->actingAs($coordinator)->post(route('dean.analytics.reset-this-month'))->assertForbidden();
        $this->actingAs($coordinator)->post(route('dean.audit-trail.reset'))->assertForbidden();
        $this->assertSame(0, SystemResetEvent::count());
    }
}
