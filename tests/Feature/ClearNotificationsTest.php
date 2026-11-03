<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClearNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_clear_notifications_only_deletes_the_signed_in_users_items(): void
    {
        $dean = User::where('username', 'dean')->firstOrFail();
        $other = User::whereKeyNot($dean->id)->firstOrFail();
        Notification::create(['user_id' => $dean->id, 'message' => 'Dean item', 'is_read' => false]);
        Notification::create(['user_id' => $other->id, 'message' => 'Other item', 'is_read' => false]);

        $this->actingAs($dean)->postJson(route('dean.notifications.clear-json'))
            ->assertOk()->assertJsonPath('count', 1)->assertJsonPath('unread_count', 0);

        $this->assertDatabaseMissing('notifications', ['user_id' => $dean->id, 'message' => 'Dean item']);
        $this->assertDatabaseHas('notifications', ['user_id' => $other->id, 'message' => 'Other item']);
        $this->actingAs($dean)->get(route('dean.notifications.recent'))
            ->assertOk()->assertJsonPath('unread_count', 0);
    }
}
