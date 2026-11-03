<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TurnoverPreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_preview_requires_verified_identity_and_leaves_accounts_unchanged(): void
    {
        $dean = User::where('username', 'dean')->firstOrFail();
        $before = DB::table('users')->orderBy('id')->get()->toJson();
        $this->artisan('turnover:preview')->assertFailed();
        $options = ['--keep-user-id' => $dean->id, '--expect-user-name' => $dean->name,
            '--expect-employee-name' => $dean->employee->full_name];
        $this->artisan('turnover:preview', array_replace($options, ['--expect-user-name' => 'Wrong identity']))->assertFailed();
        $this->artisan('turnover:preview', array_replace($options, ['--expect-employee-name' => 'Wrong identity']))->assertFailed();
        $this->artisan('turnover:preview', $options)->assertSuccessful();
        $this->assertSame($before, DB::table('users')->orderBy('id')->get()->toJson());
    }
}
