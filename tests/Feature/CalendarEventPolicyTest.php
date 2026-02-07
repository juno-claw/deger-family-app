<?php

namespace Tests\Feature;

use App\Models\CalendarEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalendarEventPolicyTest extends TestCase
{
    use RefreshDatabase;

    // ── View ──────────────────────────────────────────

    public function test_owner_can_view_event(): void
    {
        $user = User::factory()->create();
        $event = CalendarEvent::factory()->create(['owner_id' => $user->id]);

        $this->assertTrue($user->can('view', $event));
    }

    public function test_shared_user_can_view_event(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $event = CalendarEvent::factory()->create(['owner_id' => $owner->id]);
        $event->sharedWith()->attach($viewer->id, ['status' => 'accepted']);

        $this->assertTrue($viewer->can('view', $event));
    }

    public function test_unrelated_user_cannot_view_event(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $event = CalendarEvent::factory()->create(['owner_id' => $owner->id]);

        $this->assertFalse($stranger->can('view', $event));
    }

    // ── Update ────────────────────────────────────────

    public function test_owner_can_update_event(): void
    {
        $user = User::factory()->create();
        $event = CalendarEvent::factory()->create(['owner_id' => $user->id]);

        $this->assertTrue($user->can('update', $event));
    }

    public function test_accepted_shared_user_can_update_event(): void
    {
        $owner = User::factory()->create();
        $editor = User::factory()->create();
        $event = CalendarEvent::factory()->create(['owner_id' => $owner->id]);
        $event->sharedWith()->attach($editor->id, ['status' => 'accepted']);

        $this->assertTrue($editor->can('update', $event));
    }

    public function test_pending_shared_user_cannot_update_event(): void
    {
        $owner = User::factory()->create();
        $pending = User::factory()->create();
        $event = CalendarEvent::factory()->create(['owner_id' => $owner->id]);
        $event->sharedWith()->attach($pending->id, ['status' => 'pending']);

        $this->assertFalse($pending->can('update', $event));
    }

    // ── Delete ────────────────────────────────────────

    public function test_owner_can_delete_event(): void
    {
        $user = User::factory()->create();
        $event = CalendarEvent::factory()->create(['owner_id' => $user->id]);

        $this->assertTrue($user->can('delete', $event));
    }

    public function test_shared_user_cannot_delete_event(): void
    {
        $owner = User::factory()->create();
        $shared = User::factory()->create();
        $event = CalendarEvent::factory()->create(['owner_id' => $owner->id]);
        $event->sharedWith()->attach($shared->id, ['status' => 'accepted']);

        $this->assertFalse($shared->can('delete', $event));
    }

    // ── Share ─────────────────────────────────────────

    public function test_owner_can_share_event(): void
    {
        $user = User::factory()->create();
        $event = CalendarEvent::factory()->create(['owner_id' => $user->id]);

        $this->assertTrue($user->can('share', $event));
    }

    public function test_non_owner_cannot_share_event(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $event = CalendarEvent::factory()->create(['owner_id' => $owner->id]);

        $this->assertFalse($other->can('share', $event));
    }
}
