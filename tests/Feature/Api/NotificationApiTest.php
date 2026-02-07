<?php

namespace Tests\Feature\Api;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    }

    // ── Index ──────────────────────────────────────────

    public function test_can_list_own_notifications(): void
    {
        Notification::create([
            'user_id' => $this->user->id,
            'type' => 'general',
            'title' => 'Test',
            'message' => 'Hello',
        ]);

        $response = $this->getJson('/api/v1/notifications');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Test');
    }

    public function test_cannot_see_other_users_notifications(): void
    {
        $other = User::factory()->create();
        Notification::create([
            'user_id' => $other->id,
            'type' => 'general',
            'title' => 'Private',
            'message' => 'Not yours',
        ]);

        $response = $this->getJson('/api/v1/notifications');

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_notifications_are_paginated(): void
    {
        for ($i = 0; $i < 35; $i++) {
            Notification::create([
                'user_id' => $this->user->id,
                'type' => 'general',
                'title' => "Notification $i",
                'message' => "Message $i",
            ]);
        }

        $response = $this->getJson('/api/v1/notifications');

        $response->assertOk()
            ->assertJsonCount(30, 'data')
            ->assertJsonPath('meta.total', 35)
            ->assertJsonPath('meta.per_page', 30);
    }

    // ── Push ──────────────────────────────────────────

    public function test_can_push_notification_to_another_user(): void
    {
        $recipient = User::factory()->create();

        $response = $this->postJson('/api/v1/notifications/push', [
            'user_id' => $recipient->id,
            'title' => 'Hello',
            'message' => 'This is a test notification',
            'type' => 'reminder',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.title', 'Hello')
            ->assertJsonPath('data.message', 'This is a test notification')
            ->assertJsonPath('data.type', 'reminder')
            ->assertJsonPath('data.from_user.id', $this->user->id);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $recipient->id,
            'from_user_id' => $this->user->id,
            'title' => 'Hello',
        ]);
    }

    public function test_push_notification_defaults_type_to_general(): void
    {
        $recipient = User::factory()->create();

        $response = $this->postJson('/api/v1/notifications/push', [
            'user_id' => $recipient->id,
            'title' => 'No type',
            'message' => 'Test',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.type', 'general');
    }

    public function test_cannot_push_notification_without_required_fields(): void
    {
        $response = $this->postJson('/api/v1/notifications/push', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['user_id', 'title', 'message']);
    }

    public function test_cannot_push_notification_to_nonexistent_user(): void
    {
        $response = $this->postJson('/api/v1/notifications/push', [
            'user_id' => 9999,
            'title' => 'Fail',
            'message' => 'This should fail',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('user_id');
    }

    // ── Mark as Read ──────────────────────────────────

    public function test_can_mark_notification_as_read(): void
    {
        $notification = Notification::create([
            'user_id' => $this->user->id,
            'type' => 'general',
            'title' => 'Unread',
            'message' => 'Mark me',
        ]);

        $response = $this->patchJson("/api/v1/notifications/{$notification->id}/read");

        $response->assertOk();
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_cannot_mark_other_users_notification_as_read(): void
    {
        $other = User::factory()->create();
        $notification = Notification::create([
            'user_id' => $other->id,
            'type' => 'general',
            'title' => 'Not Yours',
            'message' => 'Stay away',
        ]);

        $response = $this->patchJson("/api/v1/notifications/{$notification->id}/read");

        $response->assertForbidden();
    }

    // ── Mark All as Read ──────────────────────────────

    public function test_can_mark_all_notifications_as_read(): void
    {
        for ($i = 0; $i < 5; $i++) {
            Notification::create([
                'user_id' => $this->user->id,
                'type' => 'general',
                'title' => "Note $i",
                'message' => "Message $i",
            ]);
        }

        $response = $this->postJson('/api/v1/notifications/read-all');

        $response->assertOk();
        $this->assertEquals(0, Notification::where('user_id', $this->user->id)->whereNull('read_at')->count());
    }

    public function test_mark_all_as_read_does_not_affect_other_users(): void
    {
        $other = User::factory()->create();
        Notification::create([
            'user_id' => $other->id,
            'type' => 'general',
            'title' => 'Other',
            'message' => 'Not mine',
        ]);

        $this->postJson('/api/v1/notifications/read-all');

        $this->assertNull(Notification::where('user_id', $other->id)->first()->read_at);
    }
}
