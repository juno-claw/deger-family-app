<?php

namespace Tests\Feature;

use App\Models\CalendarEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalendarEventWebTest extends TestCase
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

    public function test_calendar_page_loads_successfully(): void
    {
        $response = $this->get('/calendar');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('calendar/index')
            ->has('events')
            ->has('users')
            ->has('month')
            ->has('year')
        );
    }

    public function test_calendar_page_defaults_to_current_month(): void
    {
        $response = $this->get('/calendar');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('month', now()->month)
            ->where('year', now()->year)
        );
    }

    public function test_calendar_page_accepts_month_and_year_params(): void
    {
        $response = $this->get('/calendar?month=6&year=2026');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('month', 6)
            ->where('year', 2026)
        );
    }

    public function test_calendar_page_shows_events_for_given_month(): void
    {
        CalendarEvent::create([
            'title' => 'March Event',
            'start_at' => '2026-03-15 10:00:00',
            'owner_id' => $this->user->id,
        ]);

        CalendarEvent::create([
            'title' => 'April Event',
            'start_at' => '2026-04-15 10:00:00',
            'owner_id' => $this->user->id,
        ]);

        $response = $this->get('/calendar?month=3&year=2026');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('events', 1)
            ->where('events.0.title', 'March Event')
        );
    }

    public function test_calendar_page_includes_shared_events(): void
    {
        $owner = User::factory()->create();
        $event = CalendarEvent::create([
            'title' => 'Shared Event',
            'start_at' => now()->startOfMonth()->addDays(5),
            'owner_id' => $owner->id,
        ]);
        $event->sharedWith()->attach($this->user->id, ['status' => 'accepted']);

        $response = $this->get('/calendar');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('events', 1)
            ->where('events.0.title', 'Shared Event')
        );
    }

    // ── Store ──────────────────────────────────────────

    public function test_can_create_calendar_event(): void
    {
        $response = $this->post('/calendar/events', [
            'title' => 'New Event',
            'start_at' => '2026-03-20 14:00:00',
            'all_day' => false,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('calendar_events', [
            'title' => 'New Event',
            'owner_id' => $this->user->id,
        ]);
    }

    public function test_cannot_create_event_without_title(): void
    {
        $response = $this->post('/calendar/events', [
            'start_at' => '2026-03-20 14:00:00',
        ]);

        $response->assertSessionHasErrors('title');
    }

    // ── Update ─────────────────────────────────────────

    public function test_can_update_own_event(): void
    {
        $event = CalendarEvent::create([
            'title' => 'Old Title',
            'start_at' => '2026-03-20 14:00:00',
            'owner_id' => $this->user->id,
        ]);

        $response = $this->put("/calendar/events/{$event->id}", [
            'title' => 'New Title',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('calendar_events', [
            'id' => $event->id,
            'title' => 'New Title',
        ]);
    }

    public function test_cannot_update_other_users_event(): void
    {
        $other = User::factory()->create();
        $event = CalendarEvent::create([
            'title' => 'Not Mine',
            'start_at' => '2026-03-20 14:00:00',
            'owner_id' => $other->id,
        ]);

        $response = $this->put("/calendar/events/{$event->id}", [
            'title' => 'Hacked',
        ]);

        $response->assertForbidden();
    }

    // ── Destroy ────────────────────────────────────────

    public function test_can_delete_own_event(): void
    {
        $event = CalendarEvent::create([
            'title' => 'Delete Me',
            'start_at' => '2026-03-20 14:00:00',
            'owner_id' => $this->user->id,
        ]);

        $response = $this->delete("/calendar/events/{$event->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('calendar_events', ['id' => $event->id]);
    }

    public function test_cannot_delete_other_users_event(): void
    {
        $other = User::factory()->create();
        $event = CalendarEvent::create([
            'title' => 'Not Mine',
            'start_at' => '2026-03-20 14:00:00',
            'owner_id' => $other->id,
        ]);

        $response = $this->delete("/calendar/events/{$event->id}");

        $response->assertForbidden();
    }

    // ── Guests ─────────────────────────────────────────

    public function test_guests_cannot_access_calendar(): void
    {
        auth()->logout();

        $response = $this->get('/calendar');

        $response->assertRedirect(route('login'));
    }
}
