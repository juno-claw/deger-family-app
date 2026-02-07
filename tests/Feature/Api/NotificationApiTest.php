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

    private User $aiAgent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->aiAgent = User::factory()->create(['role' => 'ai_agent']);
        Sanctum::actingAs($this->user);
    }

    // ── Index ──────────────────────────────────────────

    public function test_can_list_own_notifications(): void
    {
        Notification::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Test',
        ]);

        $response = $this->getJson('/api/v1/notifications');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Test');
    }

    public function test_cannot_see_other_users_notifications(): void
    {
        Notification::factory()->create(['title' => 'Private']);

        $response = $this->getJson('/api/v1/notifications');

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_notifications_are_paginated(): void
    {
        Notification::factory()->count(35)->create(['user_id' => $this->user->id]);

        $response = $this->getJson('/api/v1/notifications');

        $response->assertOk()
            ->assertJsonCount(30, 'data')
            ->assertJsonPath('meta.total', 35)
            ->assertJsonPath('meta.per_page', 30);
    }

    // ── Push ──────────────────────────────────────────

    public function test_ai_agent_can_push_notification(): void
    {
        Sanctum::actingAs($this->aiAgent);

        $response = $this->postJson('/api/v1/notifications/push', [
            'user_id' => $this->user->id,
            'title' => 'Hello',
            'message' => 'This is a test notification',
            'type' => 'reminder',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.title', 'Hello')
            ->assertJsonPath('data.message', 'This is a test notification')
            ->assertJsonPath('data.type', 'reminder')
            ->assertJsonPath('data.from_user.id', $this->aiAgent->id);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->user->id,
            'from_user_id' => $this->aiAgent->id,
            'title' => 'Hello',
        ]);
    }

    public function test_push_notification_defaults_type_to_general(): void
    {
        Sanctum::actingAs($this->aiAgent);

        $response = $this->postJson('/api/v1/notifications/push', [
            'user_id' => $this->user->id,
            'title' => 'No type',
            'message' => 'Test',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.type', 'general');
    }

    public function test_non_agent_cannot_push_notification(): void
    {
        $response = $this->postJson('/api/v1/notifications/push', [
            'user_id' => $this->user->id,
            'title' => 'Hello',
            'message' => 'Test',
        ]);

        $response->assertForbidden();
    }

    public function test_cannot_push_notification_without_required_fields(): void
    {
        Sanctum::actingAs($this->aiAgent);

        $response = $this->postJson('/api/v1/notifications/push', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['user_id', 'title', 'message']);
    }

    public function test_cannot_push_notification_to_nonexistent_user(): void
    {
        Sanctum::actingAs($this->aiAgent);

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
        $notification = Notification::factory()->create(['user_id' => $this->user->id]);

        $response = $this->patchJson("/api/v1/notifications/{$notification->id}/read");

        $response->assertOk();
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_cannot_mark_other_users_notification_as_read(): void
    {
        $other = User::factory()->create();
        $notification = Notification::factory()->create(['user_id' => $other->id]);

        $response = $this->patchJson("/api/v1/notifications/{$notification->id}/read");

        $response->assertForbidden();
    }

    // ── Mark All as Read ──────────────────────────────

    public function test_can_mark_all_notifications_as_read(): void
    {
        Notification::factory()->count(5)->create(['user_id' => $this->user->id]);

        $response = $this->postJson('/api/v1/notifications/read-all');

        $response->assertOk();
        $this->assertEquals(0, Notification::where('user_id', $this->user->id)->whereNull('read_at')->count());
    }

    public function test_mark_all_as_read_does_not_affect_other_users(): void
    {
        $other = User::factory()->create();
        Notification::factory()->create(['user_id' => $other->id]);

        $this->postJson('/api/v1/notifications/read-all');

        $this->assertNull(Notification::where('user_id', $other->id)->first()->read_at);
    }
}
