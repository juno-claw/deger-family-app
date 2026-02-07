<?php

namespace Tests\Feature;

use App\Models\Note;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotePolicyTest extends TestCase
{
    use RefreshDatabase;

    // ── View ──────────────────────────────────────────

    public function test_owner_can_view_note(): void
    {
        $user = User::factory()->create();
        $note = Note::factory()->create(['owner_id' => $user->id]);

        $this->assertTrue($user->can('view', $note));
    }

    public function test_shared_user_can_view_note(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $note = Note::factory()->create(['owner_id' => $owner->id]);
        $note->sharedWith()->attach($viewer->id, ['permission' => 'view']);

        $this->assertTrue($viewer->can('view', $note));
    }

    public function test_unrelated_user_cannot_view_note(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $note = Note::factory()->create(['owner_id' => $owner->id]);

        $this->assertFalse($stranger->can('view', $note));
    }

    // ── Update ────────────────────────────────────────

    public function test_owner_can_update_note(): void
    {
        $user = User::factory()->create();
        $note = Note::factory()->create(['owner_id' => $user->id]);

        $this->assertTrue($user->can('update', $note));
    }

    public function test_shared_user_with_edit_permission_can_update(): void
    {
        $owner = User::factory()->create();
        $editor = User::factory()->create();
        $note = Note::factory()->create(['owner_id' => $owner->id]);
        $note->sharedWith()->attach($editor->id, ['permission' => 'edit']);

        $this->assertTrue($editor->can('update', $note));
    }

    public function test_shared_user_with_view_permission_cannot_update(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $note = Note::factory()->create(['owner_id' => $owner->id]);
        $note->sharedWith()->attach($viewer->id, ['permission' => 'view']);

        $this->assertFalse($viewer->can('update', $note));
    }

    // ── Delete ────────────────────────────────────────

    public function test_owner_can_delete_note(): void
    {
        $user = User::factory()->create();
        $note = Note::factory()->create(['owner_id' => $user->id]);

        $this->assertTrue($user->can('delete', $note));
    }

    public function test_shared_user_cannot_delete_note(): void
    {
        $owner = User::factory()->create();
        $shared = User::factory()->create();
        $note = Note::factory()->create(['owner_id' => $owner->id]);
        $note->sharedWith()->attach($shared->id, ['permission' => 'edit']);

        $this->assertFalse($shared->can('delete', $note));
    }

    // ── Share ─────────────────────────────────────────

    public function test_owner_can_share_note(): void
    {
        $user = User::factory()->create();
        $note = Note::factory()->create(['owner_id' => $user->id]);

        $this->assertTrue($user->can('share', $note));
    }

    public function test_non_owner_cannot_share_note(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $note = Note::factory()->create(['owner_id' => $owner->id]);

        $this->assertFalse($other->can('share', $note));
    }
}
