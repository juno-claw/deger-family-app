<?php

namespace Tests\Feature\Api;

use App\Models\GoogleCalendarConnection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GoogleCalendarApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    }

    // ── Status ──────────────────────────────────────────

    public function test_status_returns_not_connected_when_no_connection(): void
    {
        $response = $this->getJson('/api/v1/google-calendar/status');

        $response->assertOk()
            ->assertJson([
                'connected' => false,
            ]);
    }

    public function test_status_returns_connection_details_when_connected(): void
    {
        GoogleCalendarConnection::factory()->create([
            'user_id' => $this->user->id,
            'calendar_id' => 'test@group.calendar.google.com',
            'connection_type' => 'service_account',
            'enabled' => true,
        ]);

        $response = $this->getJson('/api/v1/google-calendar/status');

        $response->assertOk()
            ->assertJson([
                'connected' => true,
                'connection_type' => 'service_account',
                'calendar_id' => 'test@group.calendar.google.com',
                'enabled' => true,
            ]);
    }

    // ── Connect ─────────────────────────────────────────

    public function test_can_connect_with_service_account(): void
    {
        $response = $this->postJson('/api/v1/google-calendar/connect', [
            'calendar_id' => 'my-calendar@group.calendar.google.com',
        ]);

        $response->assertCreated()
            ->assertJson([
                'connected' => true,
                'connection_type' => 'service_account',
                'calendar_id' => 'my-calendar@group.calendar.google.com',
                'enabled' => true,
            ]);

        $this->assertDatabaseHas('google_calendar_connections', [
            'user_id' => $this->user->id,
            'calendar_id' => 'my-calendar@group.calendar.google.com',
            'connection_type' => 'service_account',
        ]);
    }

    public function test_connect_requires_calendar_id(): void
    {
        $response = $this->postJson('/api/v1/google-calendar/connect', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('calendar_id');
    }

    public function test_connect_updates_existing_connection(): void
    {
        GoogleCalendarConnection::factory()->create([
            'user_id' => $this->user->id,
            'calendar_id' => 'old@group.calendar.google.com',
        ]);

        $response = $this->postJson('/api/v1/google-calendar/connect', [
            'calendar_id' => 'new@group.calendar.google.com',
        ]);

        $response->assertCreated()
            ->assertJson([
                'calendar_id' => 'new@group.calendar.google.com',
            ]);

        $this->assertDatabaseCount('google_calendar_connections', 1);
    }

    // ── Disconnect ──────────────────────────────────────

    public function test_can_disconnect(): void
    {
        GoogleCalendarConnection::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->deleteJson('/api/v1/google-calendar/disconnect');

        $response->assertNoContent();
        $this->assertDatabaseMissing('google_calendar_connections', [
            'user_id' => $this->user->id,
        ]);
    }

    public function test_disconnect_without_connection_returns_no_content(): void
    {
        $response = $this->deleteJson('/api/v1/google-calendar/disconnect');

        $response->assertNoContent();
    }

    // ── Toggle ──────────────────────────────────────────

    public function test_can_toggle_connection(): void
    {
        GoogleCalendarConnection::factory()->create([
            'user_id' => $this->user->id,
            'enabled' => true,
        ]);

        $response = $this->patchJson('/api/v1/google-calendar/toggle');

        $response->assertOk()
            ->assertJson(['enabled' => false]);

        $response = $this->patchJson('/api/v1/google-calendar/toggle');

        $response->assertOk()
            ->assertJson(['enabled' => true]);
    }

    public function test_toggle_without_connection_returns_404(): void
    {
        $response = $this->patchJson('/api/v1/google-calendar/toggle');

        $response->assertNotFound();
    }

    // ── Authentication ──────────────────────────────────

    public function test_unauthenticated_user_cannot_access_endpoints(): void
    {
        Sanctum::actingAs(User::factory()->create());

        // Reset to unauthenticated
        $this->app['auth']->forgetGuards();

        $this->getJson('/api/v1/google-calendar/status')->assertUnauthorized();
        $this->postJson('/api/v1/google-calendar/connect')->assertUnauthorized();
        $this->deleteJson('/api/v1/google-calendar/disconnect')->assertUnauthorized();
        $this->patchJson('/api/v1/google-calendar/toggle')->assertUnauthorized();
    }
}
