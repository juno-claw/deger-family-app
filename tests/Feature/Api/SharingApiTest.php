<?php

namespace Tests\Feature\Api;

use App\Models\CalendarEvent;
use App\Models\FamilyList;
use App\Models\Note;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SharingApiTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create();
        $this->otherUser = User::factory()->create();
    }

    // ── Lists: Share ───────────────────────────────────

    public function test_owner_can_share_list(): void
    {
        Sanctum::actingAs($this->owner);
        $list = FamilyList::factory()->create(['owner_id' => $this->owner->id]);

        $response = $this->postJson("/api/v1/lists/{$list->id}/share", [
            'user_id' => $this->otherUser->id,
            'permission' => 'view',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.id', $list->id)
            ->assertJsonCount(1, 'data.shared_with');

        $this->assertDatabaseHas('list_user', [
            'list_id' => $list->id,
            'user_id' => $this->otherUser->id,
            'permission' => 'view',
        ]);
    }

    public function test_sharing_list_creates_notification(): void
    {
        Sanctum::actingAs($this->owner);
        $list = FamilyList::factory()->create(['owner_id' => $this->owner->id]);

        $this->postJson("/api/v1/lists/{$list->id}/share", [
            'user_id' => $this->otherUser->id,
            'permission' => 'edit',
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->otherUser->id,
            'from_user_id' => $this->owner->id,
            'type' => 'list_shared',
        ]);
    }

    public function test_non_owner_cannot_share_list(): void
    {
        Sanctum::actingAs($this->otherUser);
        $list = FamilyList::factory()->create(['owner_id' => $this->owner->id]);

        $response = $this->postJson("/api/v1/lists/{$list->id}/share", [
            'user_id' => $this->otherUser->id,
            'permission' => 'view',
        ]);

        $response->assertForbidden();
    }

    public function test_cannot_share_list_with_self(): void
    {
        Sanctum::actingAs($this->owner);
        $list = FamilyList::factory()->create(['owner_id' => $this->owner->id]);

        $response = $this->postJson("/api/v1/lists/{$list->id}/share", [
            'user_id' => $this->owner->id,
            'permission' => 'view',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('user_id');
    }

    public function test_share_list_requires_permission(): void
    {
        Sanctum::actingAs($this->owner);
        $list = FamilyList::factory()->create(['owner_id' => $this->owner->id]);

        $response = $this->postJson("/api/v1/lists/{$list->id}/share", [
            'user_id' => $this->otherUser->id,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('permission');
    }

    public function test_share_list_rejects_invalid_permission(): void
    {
        Sanctum::actingAs($this->owner);
        $list = FamilyList::factory()->create(['owner_id' => $this->owner->id]);

        $response = $this->postJson("/api/v1/lists/{$list->id}/share", [
            'user_id' => $this->otherUser->id,
            'permission' => 'admin',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('permission');
    }

    // ── Lists: Unshare ─────────────────────────────────

    public function test_owner_can_unshare_list(): void
    {
        Sanctum::actingAs($this->owner);
        $list = FamilyList::factory()->create(['owner_id' => $this->owner->id]);
        $list->sharedWith()->attach($this->otherUser->id, ['permission' => 'view']);

        $response = $this->deleteJson("/api/v1/lists/{$list->id}/share", [
            'user_id' => $this->otherUser->id,
        ]);

        $response->assertOk()
            ->assertJsonCount(0, 'data.shared_with');

        $this->assertDatabaseMissing('list_user', [
            'list_id' => $list->id,
            'user_id' => $this->otherUser->id,
        ]);
    }

    public function test_non_owner_cannot_unshare_list(): void
    {
        Sanctum::actingAs($this->otherUser);
        $list = FamilyList::factory()->create(['owner_id' => $this->owner->id]);
        $list->sharedWith()->attach($this->otherUser->id, ['permission' => 'view']);

        $response = $this->deleteJson("/api/v1/lists/{$list->id}/share", [
            'user_id' => $this->otherUser->id,
        ]);

        $response->assertForbidden();
    }

    // ── Notes: Share ───────────────────────────────────

    public function test_owner_can_share_note(): void
    {
        Sanctum::actingAs($this->owner);
        $note = Note::factory()->create(['owner_id' => $this->owner->id]);

        $response = $this->postJson("/api/v1/notes/{$note->id}/share", [
            'user_id' => $this->otherUser->id,
            'permission' => 'edit',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.id', $note->id)
            ->assertJsonCount(1, 'data.shared_with');

        $this->assertDatabaseHas('note_user', [
            'note_id' => $note->id,
            'user_id' => $this->otherUser->id,
            'permission' => 'edit',
        ]);
    }

    public function test_sharing_note_creates_notification(): void
    {
        Sanctum::actingAs($this->owner);
        $note = Note::factory()->create(['owner_id' => $this->owner->id]);

        $this->postJson("/api/v1/notes/{$note->id}/share", [
            'user_id' => $this->otherUser->id,
            'permission' => 'view',
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->otherUser->id,
            'from_user_id' => $this->owner->id,
            'type' => 'note_shared',
        ]);
    }

    public function test_non_owner_cannot_share_note(): void
    {
        Sanctum::actingAs($this->otherUser);
        $note = Note::factory()->create(['owner_id' => $this->owner->id]);

        $response = $this->postJson("/api/v1/notes/{$note->id}/share", [
            'user_id' => $this->otherUser->id,
            'permission' => 'view',
        ]);

        $response->assertForbidden();
    }

    // ── Notes: Unshare ─────────────────────────────────

    public function test_owner_can_unshare_note(): void
    {
        Sanctum::actingAs($this->owner);
        $note = Note::factory()->create(['owner_id' => $this->owner->id]);
        $note->sharedWith()->attach($this->otherUser->id, ['permission' => 'view']);

        $response = $this->deleteJson("/api/v1/notes/{$note->id}/share", [
            'user_id' => $this->otherUser->id,
        ]);

        $response->assertOk()
            ->assertJsonCount(0, 'data.shared_with');

        $this->assertDatabaseMissing('note_user', [
            'note_id' => $note->id,
            'user_id' => $this->otherUser->id,
        ]);
    }

    // ── Recipes: Share ─────────────────────────────────

    public function test_owner_can_share_recipe(): void
    {
        Sanctum::actingAs($this->owner);
        $recipe = Recipe::factory()->create(['owner_id' => $this->owner->id]);

        $response = $this->postJson("/api/v1/recipes/{$recipe->id}/share", [
            'user_id' => $this->otherUser->id,
            'permission' => 'view',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.id', $recipe->id)
            ->assertJsonCount(1, 'data.shared_with');

        $this->assertDatabaseHas('recipe_user', [
            'recipe_id' => $recipe->id,
            'user_id' => $this->otherUser->id,
            'permission' => 'view',
        ]);
    }

    public function test_sharing_recipe_creates_notification(): void
    {
        Sanctum::actingAs($this->owner);
        $recipe = Recipe::factory()->create(['owner_id' => $this->owner->id]);

        $this->postJson("/api/v1/recipes/{$recipe->id}/share", [
            'user_id' => $this->otherUser->id,
            'permission' => 'view',
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->otherUser->id,
            'from_user_id' => $this->owner->id,
            'type' => 'recipe_shared',
        ]);
    }

    public function test_non_owner_cannot_share_recipe(): void
    {
        Sanctum::actingAs($this->otherUser);
        $recipe = Recipe::factory()->create(['owner_id' => $this->owner->id]);

        $response = $this->postJson("/api/v1/recipes/{$recipe->id}/share", [
            'user_id' => $this->otherUser->id,
            'permission' => 'view',
        ]);

        $response->assertForbidden();
    }

    // ── Recipes: Unshare ───────────────────────────────

    public function test_owner_can_unshare_recipe(): void
    {
        Sanctum::actingAs($this->owner);
        $recipe = Recipe::factory()->create(['owner_id' => $this->owner->id]);
        $recipe->sharedWith()->attach($this->otherUser->id, ['permission' => 'view']);

        $response = $this->deleteJson("/api/v1/recipes/{$recipe->id}/share", [
            'user_id' => $this->otherUser->id,
        ]);

        $response->assertOk()
            ->assertJsonCount(0, 'data.shared_with');

        $this->assertDatabaseMissing('recipe_user', [
            'recipe_id' => $recipe->id,
            'user_id' => $this->otherUser->id,
        ]);
    }

    // ── Calendar Events: Share ─────────────────────────

    public function test_owner_can_share_calendar_event(): void
    {
        Sanctum::actingAs($this->owner);
        $event = CalendarEvent::factory()->create(['owner_id' => $this->owner->id]);

        $response = $this->postJson("/api/v1/calendar/events/{$event->id}/share", [
            'user_id' => $this->otherUser->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.id', $event->id)
            ->assertJsonCount(1, 'data.shared_with');

        $this->assertDatabaseHas('calendar_event_user', [
            'calendar_event_id' => $event->id,
            'user_id' => $this->otherUser->id,
            'status' => 'pending',
        ]);
    }

    public function test_sharing_calendar_event_does_not_require_permission(): void
    {
        Sanctum::actingAs($this->owner);
        $event = CalendarEvent::factory()->create(['owner_id' => $this->owner->id]);

        $response = $this->postJson("/api/v1/calendar/events/{$event->id}/share", [
            'user_id' => $this->otherUser->id,
        ]);

        $response->assertOk();
    }

    public function test_sharing_calendar_event_creates_notification(): void
    {
        Sanctum::actingAs($this->owner);
        $event = CalendarEvent::factory()->create(['owner_id' => $this->owner->id]);

        $this->postJson("/api/v1/calendar/events/{$event->id}/share", [
            'user_id' => $this->otherUser->id,
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->otherUser->id,
            'from_user_id' => $this->owner->id,
            'type' => 'event_shared',
        ]);
    }

    public function test_non_owner_cannot_share_calendar_event(): void
    {
        Sanctum::actingAs($this->otherUser);
        $event = CalendarEvent::factory()->create(['owner_id' => $this->owner->id]);

        $response = $this->postJson("/api/v1/calendar/events/{$event->id}/share", [
            'user_id' => $this->otherUser->id,
        ]);

        $response->assertForbidden();
    }

    // ── Calendar Events: Unshare ───────────────────────

    public function test_owner_can_unshare_calendar_event(): void
    {
        Sanctum::actingAs($this->owner);
        $event = CalendarEvent::factory()->create(['owner_id' => $this->owner->id]);
        $event->sharedWith()->attach($this->otherUser->id, ['status' => 'accepted']);

        $response = $this->deleteJson("/api/v1/calendar/events/{$event->id}/share", [
            'user_id' => $this->otherUser->id,
        ]);

        $response->assertOk()
            ->assertJsonCount(0, 'data.shared_with');

        $this->assertDatabaseMissing('calendar_event_user', [
            'calendar_event_id' => $event->id,
            'user_id' => $this->otherUser->id,
        ]);
    }

    // ── General: share requires user_id ────────────────

    public function test_share_requires_user_id(): void
    {
        Sanctum::actingAs($this->owner);
        $list = FamilyList::factory()->create(['owner_id' => $this->owner->id]);

        $response = $this->postJson("/api/v1/lists/{$list->id}/share", [
            'permission' => 'view',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('user_id');
    }

    public function test_share_requires_existing_user(): void
    {
        Sanctum::actingAs($this->owner);
        $list = FamilyList::factory()->create(['owner_id' => $this->owner->id]);

        $response = $this->postJson("/api/v1/lists/{$list->id}/share", [
            'user_id' => 99999,
            'permission' => 'view',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('user_id');
    }

    public function test_unshare_requires_user_id(): void
    {
        Sanctum::actingAs($this->owner);
        $list = FamilyList::factory()->create(['owner_id' => $this->owner->id]);

        $response = $this->deleteJson("/api/v1/lists/{$list->id}/share", []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('user_id');
    }

    // ── General: permission can be updated ─────────────

    public function test_sharing_again_updates_permission(): void
    {
        Sanctum::actingAs($this->owner);
        $note = Note::factory()->create(['owner_id' => $this->owner->id]);
        $note->sharedWith()->attach($this->otherUser->id, ['permission' => 'view']);

        $response = $this->postJson("/api/v1/notes/{$note->id}/share", [
            'user_id' => $this->otherUser->id,
            'permission' => 'edit',
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('note_user', [
            'note_id' => $note->id,
            'user_id' => $this->otherUser->id,
            'permission' => 'edit',
        ]);
    }

    // ── General: unauthenticated ───────────────────────

    public function test_unauthenticated_cannot_share(): void
    {
        $list = FamilyList::factory()->create(['owner_id' => $this->owner->id]);

        $response = $this->postJson("/api/v1/lists/{$list->id}/share", [
            'user_id' => $this->otherUser->id,
            'permission' => 'view',
        ]);

        $response->assertUnauthorized();
    }

    public function test_unauthenticated_cannot_unshare(): void
    {
        $list = FamilyList::factory()->create(['owner_id' => $this->owner->id]);

        $response = $this->deleteJson("/api/v1/lists/{$list->id}/share", [
            'user_id' => $this->otherUser->id,
        ]);

        $response->assertUnauthorized();
    }
}
