<?php

namespace Tests\Feature\Api;

use App\Models\Note;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NoteApiTest extends TestCase
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

    public function test_can_list_own_notes(): void
    {
        Note::create([
            'title' => 'My Note',
            'content' => 'Content',
            'owner_id' => $this->user->id,
        ]);

        $response = $this->getJson('/api/v1/notes');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'My Note');
    }

    public function test_can_list_shared_notes(): void
    {
        $owner = User::factory()->create();
        $note = Note::create([
            'title' => 'Shared Note',
            'content' => 'Shared Content',
            'owner_id' => $owner->id,
        ]);
        $note->sharedWith()->attach($this->user->id, ['permission' => 'view']);

        $response = $this->getJson('/api/v1/notes');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Shared Note');
    }

    public function test_cannot_see_other_users_private_notes(): void
    {
        $other = User::factory()->create();
        Note::create([
            'title' => 'Private Note',
            'content' => 'Secret',
            'owner_id' => $other->id,
        ]);

        $response = $this->getJson('/api/v1/notes');

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }

    // ── Store ──────────────────────────────────────────

    public function test_can_create_note(): void
    {
        $response = $this->postJson('/api/v1/notes', [
            'title' => 'New Note',
            'content' => 'Some text here',
            'color' => '#ff0000',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.title', 'New Note')
            ->assertJsonPath('data.content', 'Some text here')
            ->assertJsonPath('data.color', '#ff0000')
            ->assertJsonPath('data.owner.id', $this->user->id);

        $this->assertDatabaseHas('notes', [
            'title' => 'New Note',
            'owner_id' => $this->user->id,
        ]);
    }

    public function test_cannot_create_note_without_title(): void
    {
        $response = $this->postJson('/api/v1/notes', [
            'content' => 'No title',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('title');
    }

    // ── Show ──────────────────────────────────────────

    public function test_can_show_own_note(): void
    {
        $note = Note::create([
            'title' => 'My Note',
            'content' => 'Content',
            'owner_id' => $this->user->id,
        ]);

        $response = $this->getJson("/api/v1/notes/{$note->id}");

        $response->assertOk()
            ->assertJsonPath('data.title', 'My Note')
            ->assertJsonStructure([
                'data' => ['id', 'title', 'content', 'is_pinned', 'color', 'owner', 'shared_with'],
            ]);
    }

    public function test_cannot_show_other_users_private_note(): void
    {
        $other = User::factory()->create();
        $note = Note::create([
            'title' => 'Private',
            'content' => 'Secret',
            'owner_id' => $other->id,
        ]);

        $response = $this->getJson("/api/v1/notes/{$note->id}");

        $response->assertForbidden();
    }

    // ── Update ─────────────────────────────────────────

    public function test_can_update_own_note(): void
    {
        $note = Note::create([
            'title' => 'Old Title',
            'content' => 'Old Content',
            'owner_id' => $this->user->id,
        ]);

        $response = $this->putJson("/api/v1/notes/{$note->id}", [
            'title' => 'Updated Title',
            'content' => 'Updated Content',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.title', 'Updated Title')
            ->assertJsonPath('data.content', 'Updated Content');
    }

    public function test_cannot_update_other_users_note(): void
    {
        $other = User::factory()->create();
        $note = Note::create([
            'title' => 'Not Mine',
            'content' => 'Secret',
            'owner_id' => $other->id,
        ]);

        $response = $this->putJson("/api/v1/notes/{$note->id}", [
            'title' => 'Hacked',
        ]);

        $response->assertForbidden();
    }

    // ── Destroy ────────────────────────────────────────

    public function test_can_delete_own_note(): void
    {
        $note = Note::create([
            'title' => 'Delete Me',
            'content' => 'Gone',
            'owner_id' => $this->user->id,
        ]);

        $response = $this->deleteJson("/api/v1/notes/{$note->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('notes', ['id' => $note->id]);
    }

    public function test_cannot_delete_other_users_note(): void
    {
        $other = User::factory()->create();
        $note = Note::create([
            'title' => 'Not Mine',
            'content' => 'Protected',
            'owner_id' => $other->id,
        ]);

        $response = $this->deleteJson("/api/v1/notes/{$note->id}");

        $response->assertForbidden();
    }
}
