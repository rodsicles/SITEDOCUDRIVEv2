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
        $this->artisan('turnover:preview', ['--keep-user-id' => $dean->id, '--expect-name' => $dean->name])->assertFailed();
        $dean->employee->update(['full_name' => $dean->name]);
        $before = DB::table('users')->orderBy('id')->get()->toJson();
        $this->artisan('turnover:preview')->assertFailed();
        $this->artisan('turnover:preview', ['--keep-user-id' => $dean->id, '--expect-name' => 'Wrong identity'])->assertFailed();
        $this->artisan('turnover:preview', ['--keep-user-id' => $dean->id, '--expect-name' => $dean->name])->assertSuccessful();
        $this->assertSame($before, DB::table('users')->orderBy('id')->get()->toJson());
    }
}
