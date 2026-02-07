<?php

namespace Tests\Feature\Api;

use App\Models\FamilyList;
use App\Models\ListItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ListApiTest extends TestCase
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

    public function test_can_list_own_lists(): void
    {
        $list = FamilyList::create([
            'title' => 'My List',
            'type' => 'todo',
            'owner_id' => $this->user->id,
        ]);

        $response = $this->getJson('/api/v1/lists');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'My List');
    }

    public function test_can_list_shared_lists(): void
    {
        $owner = User::factory()->create();
        $list = FamilyList::create([
            'title' => 'Shared List',
            'type' => 'shopping',
            'owner_id' => $owner->id,
        ]);
        $list->sharedWith()->attach($this->user->id, ['permission' => 'view']);

        $response = $this->getJson('/api/v1/lists');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Shared List');
    }

    public function test_cannot_see_other_users_private_lists(): void
    {
        $other = User::factory()->create();
        FamilyList::create([
            'title' => 'Private List',
            'type' => 'todo',
            'owner_id' => $other->id,
        ]);

        $response = $this->getJson('/api/v1/lists');

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }

    // ── Store ──────────────────────────────────────────

    public function test_can_create_list(): void
    {
        $response = $this->postJson('/api/v1/lists', [
            'title' => 'Grocery List',
            'type' => 'shopping',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.title', 'Grocery List')
            ->assertJsonPath('data.type', 'shopping')
            ->assertJsonPath('data.owner.id', $this->user->id);

        $this->assertDatabaseHas('lists', [
            'title' => 'Grocery List',
            'owner_id' => $this->user->id,
        ]);
    }

    public function test_cannot_create_list_without_title(): void
    {
        $response = $this->postJson('/api/v1/lists', [
            'type' => 'todo',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('title');
    }

    public function test_cannot_create_list_with_invalid_type(): void
    {
        $response = $this->postJson('/api/v1/lists', [
            'title' => 'Test',
            'type' => 'invalid',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('type');
    }

    // ── Show ──────────────────────────────────────────

    public function test_can_show_own_list_with_items(): void
    {
        $list = FamilyList::create([
            'title' => 'My List',
            'type' => 'todo',
            'owner_id' => $this->user->id,
        ]);
        ListItem::create([
            'list_id' => $list->id,
            'content' => 'Task 1',
            'position' => 0,
            'created_by' => $this->user->id,
        ]);

        $response = $this->getJson("/api/v1/lists/{$list->id}");

        $response->assertOk()
            ->assertJsonPath('data.title', 'My List')
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.content', 'Task 1');
    }

    public function test_cannot_show_other_users_private_list(): void
    {
        $other = User::factory()->create();
        $list = FamilyList::create([
            'title' => 'Private',
            'type' => 'todo',
            'owner_id' => $other->id,
        ]);

        $response = $this->getJson("/api/v1/lists/{$list->id}");

        $response->assertForbidden();
    }

    // ── Update ─────────────────────────────────────────

    public function test_can_update_own_list(): void
    {
        $list = FamilyList::create([
            'title' => 'Old Title',
            'type' => 'todo',
            'owner_id' => $this->user->id,
        ]);

        $response = $this->putJson("/api/v1/lists/{$list->id}", [
            'title' => 'New Title',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.title', 'New Title');
    }

    public function test_cannot_update_other_users_list(): void
    {
        $other = User::factory()->create();
        $list = FamilyList::create([
            'title' => 'Not Mine',
            'type' => 'todo',
            'owner_id' => $other->id,
        ]);

        $response = $this->putJson("/api/v1/lists/{$list->id}", [
            'title' => 'Hacked',
        ]);

        $response->assertForbidden();
    }

    // ── Destroy ────────────────────────────────────────

    public function test_can_delete_own_list(): void
    {
        $list = FamilyList::create([
            'title' => 'Delete Me',
            'type' => 'todo',
            'owner_id' => $this->user->id,
        ]);

        $response = $this->deleteJson("/api/v1/lists/{$list->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('lists', ['id' => $list->id]);
    }

    public function test_cannot_delete_other_users_list(): void
    {
        $other = User::factory()->create();
        $list = FamilyList::create([
            'title' => 'Not Mine',
            'type' => 'todo',
            'owner_id' => $other->id,
        ]);

        $response = $this->deleteJson("/api/v1/lists/{$list->id}");

        $response->assertForbidden();
    }

    // ── List Items ─────────────────────────────────────

    public function test_can_add_item_to_own_list(): void
    {
        $list = FamilyList::create([
            'title' => 'List',
            'type' => 'todo',
            'owner_id' => $this->user->id,
        ]);

        $response = $this->postJson("/api/v1/lists/{$list->id}/items", [
            'content' => 'Buy milk',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.content', 'Buy milk')
            ->assertJsonPath('data.created_by', $this->user->id);
    }

    public function test_can_update_item(): void
    {
        $list = FamilyList::create([
            'title' => 'List',
            'type' => 'todo',
            'owner_id' => $this->user->id,
        ]);
        $item = ListItem::create([
            'list_id' => $list->id,
            'content' => 'Original',
            'position' => 0,
            'created_by' => $this->user->id,
        ]);

        $response = $this->putJson("/api/v1/lists/{$list->id}/items/{$item->id}", [
            'is_completed' => true,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.is_completed', true);
    }

    public function test_can_delete_item(): void
    {
        $list = FamilyList::create([
            'title' => 'List',
            'type' => 'todo',
            'owner_id' => $this->user->id,
        ]);
        $item = ListItem::create([
            'list_id' => $list->id,
            'content' => 'Delete me',
            'position' => 0,
            'created_by' => $this->user->id,
        ]);

        $response = $this->deleteJson("/api/v1/lists/{$list->id}/items/{$item->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('list_items', ['id' => $item->id]);
    }
}
