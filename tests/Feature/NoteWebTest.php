<?php

namespace Tests\Feature;

use App\Models\Note;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NoteWebTest extends TestCase
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

    public function test_notes_page_loads_successfully(): void
    {
        $response = $this->get('/notes');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('notes/index')
            ->has('ownNotes')
            ->has('sharedNotes')
            ->has('users')
        );
    }

    // ── Show ──────────────────────────────────────────

    public function test_can_view_own_note(): void
    {
        $note = Note::create([
            'title' => 'My Note',
            'content' => 'Content here',
            'owner_id' => $this->user->id,
        ]);

        $response = $this->get("/notes/{$note->id}");

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('notes/show')
            ->where('note.title', 'My Note')
        );
    }

    // ── Update with HTML content ──────────────────────

    public function test_can_update_note_with_html_content(): void
    {
        $note = Note::create([
            'title' => 'Test Note',
            'content' => 'Plain text',
            'owner_id' => $this->user->id,
        ]);

        $htmlContent = '<h2>Heading</h2><p><strong>Bold</strong> and <em>italic</em> text</p>';

        $response = $this->put("/notes/{$note->id}", [
            'title' => 'Updated Note',
            'content' => $htmlContent,
        ], ['X-Inertia' => 'true', 'X-Inertia-Version' => '']);

        $response->assertRedirect();

        $note->refresh();
        $this->assertEquals('Updated Note', $note->title);
        $this->assertEquals($htmlContent, $note->content);
    }

    public function test_can_update_note_with_html_via_autosave(): void
    {
        $note = Note::create([
            'title' => 'Autosave Note',
            'content' => 'Original',
            'owner_id' => $this->user->id,
        ]);

        $htmlContent = '<p>Text with <u>underline</u> and <s>strikethrough</s></p>';

        // Autosave sends without X-Inertia header, returns JSON
        $response = $this->put("/notes/{$note->id}", [
            'title' => 'Autosave Note',
            'content' => $htmlContent,
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $note->refresh();
        $this->assertEquals($htmlContent, $note->content);
    }

    // ── Store ─────────────────────────────────────────

    public function test_can_create_note(): void
    {
        $response = $this->post('/notes', [
            'title' => 'New Note',
            'content' => '<p>Rich <strong>content</strong></p>',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('notes', [
            'title' => 'New Note',
            'owner_id' => $this->user->id,
        ]);
    }

    // ── Pin ───────────────────────────────────────────

    public function test_can_toggle_pin(): void
    {
        $note = Note::create([
            'title' => 'Pin Me',
            'content' => 'Content',
            'owner_id' => $this->user->id,
            'is_pinned' => false,
        ]);

        $response = $this->patch("/notes/{$note->id}/pin");

        $response->assertRedirect();
        $this->assertTrue($note->fresh()->is_pinned);
    }

    // ── Destroy ───────────────────────────────────────

    public function test_can_delete_own_note(): void
    {
        $note = Note::create([
            'title' => 'Delete Me',
            'content' => 'Gone',
            'owner_id' => $this->user->id,
        ]);

        $response = $this->delete("/notes/{$note->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('notes', ['id' => $note->id]);
    }

    // ── Guests ────────────────────────────────────────

    public function test_guests_cannot_access_notes(): void
    {
        auth()->logout();

        $response = $this->get('/notes');

        $response->assertRedirect(route('login'));
    }
}
