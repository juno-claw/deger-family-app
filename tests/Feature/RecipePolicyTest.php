<?php

namespace Tests\Feature;

use App\Models\Recipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecipePolicyTest extends TestCase
{
    use RefreshDatabase;

    // ── View ──────────────────────────────────────────

    public function test_owner_can_view_recipe(): void
    {
        $user = User::factory()->create();
        $recipe = Recipe::factory()->create(['owner_id' => $user->id]);

        $this->assertTrue($user->can('view', $recipe));
    }

    public function test_shared_user_can_view_recipe(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $recipe = Recipe::factory()->create(['owner_id' => $owner->id]);
        $recipe->sharedWith()->attach($viewer->id, ['permission' => 'view']);

        $this->assertTrue($viewer->can('view', $recipe));
    }

    public function test_unrelated_user_cannot_view_recipe(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $recipe = Recipe::factory()->create(['owner_id' => $owner->id]);

        $this->assertFalse($stranger->can('view', $recipe));
    }

    // ── Update ────────────────────────────────────────

    public function test_owner_can_update_recipe(): void
    {
        $user = User::factory()->create();
        $recipe = Recipe::factory()->create(['owner_id' => $user->id]);

        $this->assertTrue($user->can('update', $recipe));
    }

    public function test_shared_user_with_edit_permission_can_update(): void
    {
        $owner = User::factory()->create();
        $editor = User::factory()->create();
        $recipe = Recipe::factory()->create(['owner_id' => $owner->id]);
        $recipe->sharedWith()->attach($editor->id, ['permission' => 'edit']);

        $this->assertTrue($editor->can('update', $recipe));
    }

    public function test_shared_user_with_view_permission_cannot_update(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $recipe = Recipe::factory()->create(['owner_id' => $owner->id]);
        $recipe->sharedWith()->attach($viewer->id, ['permission' => 'view']);

        $this->assertFalse($viewer->can('update', $recipe));
    }

    // ── Delete ────────────────────────────────────────

    public function test_owner_can_delete_recipe(): void
    {
        $user = User::factory()->create();
        $recipe = Recipe::factory()->create(['owner_id' => $user->id]);

        $this->assertTrue($user->can('delete', $recipe));
    }

    public function test_shared_user_cannot_delete_recipe(): void
    {
        $owner = User::factory()->create();
        $shared = User::factory()->create();
        $recipe = Recipe::factory()->create(['owner_id' => $owner->id]);
        $recipe->sharedWith()->attach($shared->id, ['permission' => 'edit']);

        $this->assertFalse($shared->can('delete', $recipe));
    }

    // ── Share ─────────────────────────────────────────

    public function test_owner_can_share_recipe(): void
    {
        $user = User::factory()->create();
        $recipe = Recipe::factory()->create(['owner_id' => $user->id]);

        $this->assertTrue($user->can('share', $recipe));
    }

    public function test_non_owner_cannot_share_recipe(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $recipe = Recipe::factory()->create(['owner_id' => $owner->id]);

        $this->assertFalse($other->can('share', $recipe));
    }
}
