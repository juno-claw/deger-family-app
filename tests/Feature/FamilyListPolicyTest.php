<?php

namespace Tests\Feature;

use App\Models\FamilyList;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FamilyListPolicyTest extends TestCase
{
    use RefreshDatabase;

    // ── View ──────────────────────────────────────────

    public function test_owner_can_view_list(): void
    {
        $user = User::factory()->create();
        $list = FamilyList::factory()->create(['owner_id' => $user->id]);

        $this->assertTrue($user->can('view', $list));
    }

    public function test_shared_user_can_view_list(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $list = FamilyList::factory()->create(['owner_id' => $owner->id]);
        $list->sharedWith()->attach($viewer->id, ['permission' => 'view']);

        $this->assertTrue($viewer->can('view', $list));
    }

    public function test_unrelated_user_cannot_view_list(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $list = FamilyList::factory()->create(['owner_id' => $owner->id]);

        $this->assertFalse($stranger->can('view', $list));
    }

    // ── Update ────────────────────────────────────────

    public function test_owner_can_update_list(): void
    {
        $user = User::factory()->create();
        $list = FamilyList::factory()->create(['owner_id' => $user->id]);

        $this->assertTrue($user->can('update', $list));
    }

    public function test_shared_user_with_edit_permission_can_update(): void
    {
        $owner = User::factory()->create();
        $editor = User::factory()->create();
        $list = FamilyList::factory()->create(['owner_id' => $owner->id]);
        $list->sharedWith()->attach($editor->id, ['permission' => 'edit']);

        $this->assertTrue($editor->can('update', $list));
    }

    public function test_shared_user_with_view_permission_cannot_update(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $list = FamilyList::factory()->create(['owner_id' => $owner->id]);
        $list->sharedWith()->attach($viewer->id, ['permission' => 'view']);

        $this->assertFalse($viewer->can('update', $list));
    }

    // ── Delete ────────────────────────────────────────

    public function test_owner_can_delete_list(): void
    {
        $user = User::factory()->create();
        $list = FamilyList::factory()->create(['owner_id' => $user->id]);

        $this->assertTrue($user->can('delete', $list));
    }

    public function test_shared_user_cannot_delete_list(): void
    {
        $owner = User::factory()->create();
        $shared = User::factory()->create();
        $list = FamilyList::factory()->create(['owner_id' => $owner->id]);
        $list->sharedWith()->attach($shared->id, ['permission' => 'edit']);

        $this->assertFalse($shared->can('delete', $list));
    }

    // ── Share ─────────────────────────────────────────

    public function test_owner_can_share_list(): void
    {
        $user = User::factory()->create();
        $list = FamilyList::factory()->create(['owner_id' => $user->id]);

        $this->assertTrue($user->can('share', $list));
    }

    public function test_non_owner_cannot_share_list(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $list = FamilyList::factory()->create(['owner_id' => $owner->id]);

        $this->assertFalse($other->can('share', $list));
    }
}
