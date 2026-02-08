<?php

namespace Tests\Feature\Api;

use App\Models\CalendarEvent;
use App\Models\FamilyList;
use App\Models\ListItem;
use App\Models\Note;
use App\Models\Recipe;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShortcutApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'name' => 'Olli',
            'email' => 'olli@deger.family',
        ]);
    }

    // ── Localhost Middleware ───────────────────────────

    public function test_non_localhost_request_returns_403(): void
    {
        $response = $this->getJson('/api/v1/shortcuts/einkauf?user=olli', [
            'REMOTE_ADDR' => '192.168.1.100',
        ]);

        $response->assertForbidden();
    }

    // ── User Parameter ────────────────────────────────

    public function test_missing_user_param_returns_400(): void
    {
        $response = $this->getJson('/api/v1/shortcuts/einkauf');

        $response->assertStatus(400);
    }

    public function test_unknown_user_returns_400(): void
    {
        $response = $this->getJson('/api/v1/shortcuts/einkauf?user=unknown');

        $response->assertStatus(400);
    }

    public function test_user_param_is_case_insensitive(): void
    {
        $response = $this->getJson('/api/v1/shortcuts/notizen?user=Olli');

        $response->assertOk();
    }

    // ── Einkauf ───────────────────────────────────────

    public function test_einkauf_returns_formatted_shopping_list(): void
    {
        $list = FamilyList::factory()->shopping()->create([
            'title' => 'Wocheneinkauf',
            'owner_id' => $this->user->id,
        ]);

        ListItem::factory()->create(['list_id' => $list->id, 'content' => 'Milch', 'is_completed' => false]);
        ListItem::factory()->create(['list_id' => $list->id, 'content' => 'Brot', 'is_completed' => false]);
        ListItem::factory()->completed()->create(['list_id' => $list->id, 'content' => 'Butter']);

        $response = $this->getJson('/api/v1/shortcuts/einkauf?user=olli');

        $response->assertOk();

        $text = $response->json('text');
        $this->assertStringContainsString('🛒 Wocheneinkauf', $text);
        $this->assertStringContainsString('◻️ Milch', $text);
        $this->assertStringContainsString('◻️ Brot', $text);
        $this->assertStringContainsString('✅ Butter (erledigt)', $text);
        $this->assertStringContainsString('📊 1 von 3 erledigt', $text);
    }

    public function test_einkauf_shows_open_items_before_completed(): void
    {
        $list = FamilyList::factory()->shopping()->create(['owner_id' => $this->user->id]);

        ListItem::factory()->completed()->create(['list_id' => $list->id, 'content' => 'Erledigt']);
        ListItem::factory()->create(['list_id' => $list->id, 'content' => 'Offen', 'is_completed' => false]);

        $response = $this->getJson('/api/v1/shortcuts/einkauf?user=olli');

        $text = $response->json('text');
        $openPos = strpos($text, '◻️ Offen');
        $completedPos = strpos($text, '✅ Erledigt');
        $this->assertLessThan($completedPos, $openPos);
    }

    public function test_einkauf_empty_state(): void
    {
        $response = $this->getJson('/api/v1/shortcuts/einkauf?user=olli');

        $response->assertOk()
            ->assertJsonPath('text', 'Keine Einkaufsliste vorhanden.');
    }

    public function test_einkauf_returns_most_recently_updated_list(): void
    {
        FamilyList::factory()->shopping()->create([
            'title' => 'Alte Liste',
            'owner_id' => $this->user->id,
            'updated_at' => now()->subDay(),
        ]);
        FamilyList::factory()->shopping()->create([
            'title' => 'Neue Liste',
            'owner_id' => $this->user->id,
            'updated_at' => now(),
        ]);

        $response = $this->getJson('/api/v1/shortcuts/einkauf?user=olli');

        $this->assertStringContainsString('Neue Liste', $response->json('text'));
        $this->assertStringNotContainsString('Alte Liste', $response->json('text'));
    }

    public function test_einkauf_includes_shared_lists(): void
    {
        $otherUser = User::factory()->create();
        $list = FamilyList::factory()->shopping()->create([
            'title' => 'Geteilte Liste',
            'owner_id' => $otherUser->id,
        ]);
        $list->sharedWith()->attach($this->user->id, ['permission' => 'edit']);

        ListItem::factory()->create(['list_id' => $list->id, 'content' => 'Geteiltes Item', 'is_completed' => false]);

        $response = $this->getJson('/api/v1/shortcuts/einkauf?user=olli');

        $response->assertOk();
        $this->assertStringContainsString('Geteilte Liste', $response->json('text'));
        $this->assertStringContainsString('Geteiltes Item', $response->json('text'));
    }

    // ── Todo ──────────────────────────────────────────

    public function test_todo_returns_formatted_todo_list(): void
    {
        $list = FamilyList::factory()->todo()->create([
            'title' => 'Aufgaben',
            'owner_id' => $this->user->id,
        ]);

        ListItem::factory()->create(['list_id' => $list->id, 'content' => 'Steuererklärung', 'is_completed' => false]);
        ListItem::factory()->completed()->create(['list_id' => $list->id, 'content' => 'Müll rausbringen']);

        $response = $this->getJson('/api/v1/shortcuts/todo?user=olli');

        $response->assertOk();

        $text = $response->json('text');
        $this->assertStringContainsString('📋 Aufgaben', $text);
        $this->assertStringContainsString('◻️ Steuererklärung', $text);
        $this->assertStringContainsString('✅ Müll rausbringen (erledigt)', $text);
        $this->assertStringContainsString('📊 1 von 2 erledigt', $text);
    }

    public function test_todo_empty_state(): void
    {
        $response = $this->getJson('/api/v1/shortcuts/todo?user=olli');

        $response->assertOk()
            ->assertJsonPath('text', 'Keine Todo-Liste vorhanden.');
    }

    // ── Kalender ──────────────────────────────────────

    public function test_kalender_returns_grouped_events(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 8, 10, 0, 0));

        CalendarEvent::factory()->create([
            'title' => 'Arzttermin',
            'start_at' => Carbon::create(2026, 2, 8, 14, 0),
            'end_at' => Carbon::create(2026, 2, 8, 15, 0),
            'all_day' => false,
            'owner_id' => $this->user->id,
        ]);

        CalendarEvent::factory()->create([
            'title' => 'Elternabend',
            'start_at' => Carbon::create(2026, 2, 9, 10, 0),
            'end_at' => Carbon::create(2026, 2, 9, 11, 30),
            'all_day' => false,
            'owner_id' => $this->user->id,
        ]);

        $response = $this->getJson('/api/v1/shortcuts/kalender?user=olli');

        $response->assertOk();

        $text = $response->json('text');
        $this->assertStringContainsString('📅 Termine (nächste 7 Tage)', $text);
        $this->assertStringContainsString('Heute, 08.02.', $text);
        $this->assertStringContainsString('🕐 14:00–15:00 Arzttermin', $text);
        $this->assertStringContainsString('Morgen, 09.02.', $text);
        $this->assertStringContainsString('🕐 10:00–11:30 Elternabend', $text);

        Carbon::setTestNow();
    }

    public function test_kalender_shows_all_day_events(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 8, 10, 0, 0));

        CalendarEvent::factory()->allDay()->create([
            'title' => 'Geburtstag',
            'start_at' => Carbon::create(2026, 2, 8, 0, 0),
            'owner_id' => $this->user->id,
        ]);

        $response = $this->getJson('/api/v1/shortcuts/kalender?user=olli');

        $text = $response->json('text');
        $this->assertStringContainsString('📌 Ganztägig: Geburtstag', $text);

        Carbon::setTestNow();
    }

    public function test_kalender_uses_weekday_label_for_later_days(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 8, 10, 0, 0)); // Sunday

        CalendarEvent::factory()->create([
            'title' => 'Zahnarzt',
            'start_at' => Carbon::create(2026, 2, 11, 9, 0), // Wednesday
            'end_at' => null,
            'all_day' => false,
            'owner_id' => $this->user->id,
        ]);

        $response = $this->getJson('/api/v1/shortcuts/kalender?user=olli');

        $text = $response->json('text');
        $this->assertStringContainsString('Mi, 11.02.', $text);
        $this->assertStringContainsString('🕐 09:00 Zahnarzt', $text);

        Carbon::setTestNow();
    }

    public function test_kalender_empty_state(): void
    {
        $response = $this->getJson('/api/v1/shortcuts/kalender?user=olli');

        $response->assertOk()
            ->assertJsonPath('text', '📅 Keine Termine in den nächsten 7 Tagen.');
    }

    public function test_kalender_includes_shared_events(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 8, 10, 0, 0));

        $otherUser = User::factory()->create();
        $event = CalendarEvent::factory()->create([
            'title' => 'Geteilter Termin',
            'start_at' => Carbon::create(2026, 2, 8, 16, 0),
            'end_at' => Carbon::create(2026, 2, 8, 17, 0),
            'all_day' => false,
            'owner_id' => $otherUser->id,
        ]);
        $event->sharedWith()->attach($this->user->id, ['status' => 'accepted']);

        $response = $this->getJson('/api/v1/shortcuts/kalender?user=olli');

        $this->assertStringContainsString('Geteilter Termin', $response->json('text'));

        Carbon::setTestNow();
    }

    // ── Notizen ───────────────────────────────────────

    public function test_notizen_returns_pinned_first(): void
    {
        Note::factory()->create([
            'title' => 'Normale Notiz',
            'owner_id' => $this->user->id,
            'is_pinned' => false,
        ]);
        Note::factory()->pinned()->create([
            'title' => 'Wichtige Notiz',
            'owner_id' => $this->user->id,
        ]);

        $response = $this->getJson('/api/v1/shortcuts/notizen?user=olli');

        $response->assertOk();

        $text = $response->json('text');
        $this->assertStringContainsString('📝 Notizen', $text);
        $this->assertStringContainsString('📌 Wichtige Notiz', $text);
        $this->assertStringContainsString('📝 Normale Notiz', $text);
        $this->assertStringContainsString('2 Notizen gesamt', $text);

        $pinnedPos = strpos($text, '📌 Wichtige Notiz');
        $normalPos = strpos($text, '📝 Normale Notiz');
        $this->assertLessThan($normalPos, $pinnedPos);
    }

    public function test_notizen_empty_state(): void
    {
        $response = $this->getJson('/api/v1/shortcuts/notizen?user=olli');

        $response->assertOk()
            ->assertJsonPath('text', '📝 Keine Notizen vorhanden.');
    }

    public function test_notizen_includes_shared_notes(): void
    {
        $otherUser = User::factory()->create();
        $note = Note::factory()->create([
            'title' => 'Geteilte Notiz',
            'owner_id' => $otherUser->id,
        ]);
        $note->sharedWith()->attach($this->user->id, ['permission' => 'view']);

        $response = $this->getJson('/api/v1/shortcuts/notizen?user=olli');

        $this->assertStringContainsString('Geteilte Notiz', $response->json('text'));
    }

    // ── Rezepte ───────────────────────────────────────

    public function test_rezepte_returns_favorites_first(): void
    {
        Recipe::factory()->create([
            'title' => 'Pfannkuchen',
            'category' => 'baking',
            'prep_time' => 10,
            'cook_time' => 15,
            'owner_id' => $this->user->id,
            'is_favorite' => false,
        ]);
        Recipe::factory()->favorite()->create([
            'title' => 'Spaghetti Bolognese',
            'category' => 'cooking',
            'prep_time' => 15,
            'cook_time' => 30,
            'owner_id' => $this->user->id,
        ]);

        $response = $this->getJson('/api/v1/shortcuts/rezepte?user=olli');

        $response->assertOk();

        $text = $response->json('text');
        $this->assertStringContainsString('👨‍🍳 Rezepte', $text);
        $this->assertStringContainsString('⭐ Spaghetti Bolognese (Kochen, 45 Min.)', $text);
        $this->assertStringContainsString('📖 Pfannkuchen (Backen, 25 Min.)', $text);
        $this->assertStringContainsString('2 Rezepte gesamt', $text);

        $favPos = strpos($text, '⭐ Spaghetti Bolognese');
        $normalPos = strpos($text, '📖 Pfannkuchen');
        $this->assertLessThan($normalPos, $favPos);
    }

    public function test_rezepte_empty_state(): void
    {
        $response = $this->getJson('/api/v1/shortcuts/rezepte?user=olli');

        $response->assertOk()
            ->assertJsonPath('text', '👨‍🍳 Keine Rezepte vorhanden.');
    }

    public function test_rezepte_handles_missing_times(): void
    {
        Recipe::factory()->create([
            'title' => 'Smoothie',
            'category' => 'drink',
            'prep_time' => null,
            'cook_time' => null,
            'owner_id' => $this->user->id,
        ]);

        $response = $this->getJson('/api/v1/shortcuts/rezepte?user=olli');

        $text = $response->json('text');
        $this->assertStringContainsString('📖 Smoothie (Getränk)', $text);
        $this->assertStringNotContainsString('Min.', $text);
    }

    public function test_rezepte_includes_shared_recipes(): void
    {
        $otherUser = User::factory()->create();
        $recipe = Recipe::factory()->create([
            'title' => 'Geteiltes Rezept',
            'category' => 'cooking',
            'prep_time' => 10,
            'cook_time' => 20,
            'owner_id' => $otherUser->id,
        ]);
        $recipe->sharedWith()->attach($this->user->id, ['permission' => 'view']);

        $response = $this->getJson('/api/v1/shortcuts/rezepte?user=olli');

        $this->assertStringContainsString('Geteiltes Rezept', $response->json('text'));
    }
}
