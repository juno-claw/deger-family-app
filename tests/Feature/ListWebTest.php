<?php

namespace Tests\Feature;

use App\Models\FamilyList;
use App\Models\ListItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListWebTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    // ── Index ──────────────────────────────────────────

    public function test_lists_page_loads_successfully(): void
    {
        $response = $this->get('/lists');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('lists/index')
            ->has('ownLists')
            ->has('sharedLists')
        );
    }

    public function test_lists_page_shows_own_and_shared_lists(): void
    {
        FamilyList::factory()->create(['owner_id' => $this->user->id, 'title' => 'My List']);

        $other = User::factory()->create();
        $sharedList = FamilyList::factory()->create(['owner_id' => $other->id, 'title' => 'Shared List']);
        $sharedList->sharedWith()->attach($this->user->id, ['permission' => 'view']);

        $response = $this->get('/lists');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('ownLists', 1)
            ->has('sharedLists', 1)
        );
    }

    // ── Store ──────────────────────────────────────────

    public function test_can_create_list(): void
    {
        $response = $this->post('/lists', [
            'title' => 'Einkaufsliste',
            'type' => 'shopping',
        ]);

        $response->assertRedirect(route('lists.index'));
        $this->assertDatabaseHas('lists', [
            'title' => 'Einkaufsliste',
            'type' => 'shopping',
            'owner_id' => $this->user->id,
        ]);
    }

    public function test_cannot_create_list_without_title(): void
    {
        $response = $this->post('/lists', [
            'type' => 'todo',
        ]);

        $response->assertSessionHasErrors('title');
    }

    // ── Show ──────────────────────────────────────────

    public function test_can_view_own_list(): void
    {
        $list = FamilyList::factory()->create(['owner_id' => $this->user->id]);

        $response = $this->get("/lists/{$list->id}");

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('lists/show')
            ->has('list')
            ->has('users')
        );
    }

    public function test_cannot_view_other_users_list(): void
    {
        $other = User::factory()->create();
        $list = FamilyList::factory()->create(['owner_id' => $other->id]);

        $response = $this->get("/lists/{$list->id}");

        $response->assertForbidden();
    }

    // ── Update ──────────────────────────────────────────

    public function test_can_update_own_list(): void
    {
        $list = FamilyList::factory()->create(['owner_id' => $this->user->id, 'title' => 'Old']);

        $response = $this->put("/lists/{$list->id}", ['title' => 'New']);

        $response->assertRedirect();
        $this->assertDatabaseHas('lists', ['id' => $list->id, 'title' => 'New']);
    }

    // ── Destroy ──────────────────────────────────────────

    public function test_can_delete_own_list(): void
    {
        $list = FamilyList::factory()->create(['owner_id' => $this->user->id]);

        $response = $this->delete("/lists/{$list->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('lists', ['id' => $list->id]);
    }

    public function test_cannot_delete_other_users_list(): void
    {
        $other = User::factory()->create();
        $list = FamilyList::factory()->create(['owner_id' => $other->id]);

        $response = $this->delete("/lists/{$list->id}");

        $response->assertForbidden();
    }

    // ── Share / Unshare ──────────────────────────────────

    public function test_owner_can_share_list(): void
    {
        $list = FamilyList::factory()->create(['owner_id' => $this->user->id]);
        $other = User::factory()->create();

        $response = $this->post("/lists/{$list->id}/share", [
            'user_id' => $other->id,
            'permission' => 'edit',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('list_user', [
            'list_id' => $list->id,
            'user_id' => $other->id,
            'permission' => 'edit',
        ]);
    }

    public function test_cannot_share_list_with_self(): void
    {
        $list = FamilyList::factory()->create(['owner_id' => $this->user->id]);

        $response = $this->post("/lists/{$list->id}/share", [
            'user_id' => $this->user->id,
            'permission' => 'view',
        ]);

        $response->assertSessionHasErrors('user_id');
    }

    public function test_owner_can_unshare_list(): void
    {
        $list = FamilyList::factory()->create(['owner_id' => $this->user->id]);
        $other = User::factory()->create();
        $list->sharedWith()->attach($other->id, ['permission' => 'view']);

        $response = $this->delete("/lists/{$list->id}/unshare", [
            'user_id' => $other->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseMissing('list_user', [
            'list_id' => $list->id,
            'user_id' => $other->id,
        ]);
    }

    // ── List Items ──────────────────────────────────────

    public function test_can_add_item_to_own_list(): void
    {
        $list = FamilyList::factory()->create(['owner_id' => $this->user->id]);

        $response = $this->post("/lists/{$list->id}/items", [
            'content' => 'Milch',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('list_items', [
            'list_id' => $list->id,
            'content' => 'Milch',
        ]);
    }

    public function test_can_toggle_item_completion(): void
    {
        $list = FamilyList::factory()->create(['owner_id' => $this->user->id]);
        $item = ListItem::factory()->create([
            'list_id' => $list->id,
            'created_by' => $this->user->id,
            'is_completed' => false,
        ]);

        $response = $this->put("/lists/{$list->id}/items/{$item->id}", [
            'is_completed' => true,
        ]);

        $response->assertRedirect();
        $this->assertTrue($item->fresh()->is_completed);
    }

    public function test_can_delete_item(): void
    {
        $list = FamilyList::factory()->create(['owner_id' => $this->user->id]);
        $item = ListItem::factory()->create([
            'list_id' => $list->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->delete("/lists/{$list->id}/items/{$item->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('list_items', ['id' => $item->id]);
    }

    public function test_can_reorder_items(): void
    {
        $list = FamilyList::factory()->create(['owner_id' => $this->user->id]);
        $item1 = ListItem::factory()->create(['list_id' => $list->id, 'position' => 0, 'created_by' => $this->user->id]);
        $item2 = ListItem::factory()->create(['list_id' => $list->id, 'position' => 1, 'created_by' => $this->user->id]);

        $response = $this->post("/lists/{$list->id}/items/reorder", [
            'items' => [$item2->id, $item1->id],
        ]);

        $response->assertRedirect();
        $this->assertEquals(1, $item1->fresh()->position);
        $this->assertEquals(0, $item2->fresh()->position);
    }

    // ── Guests ──────────────────────────────────────────

    public function test_guests_cannot_access_lists(): void
    {
        auth()->logout();

        $response = $this->get('/lists');

        $response->assertRedirect(route('login'));
    }
}
