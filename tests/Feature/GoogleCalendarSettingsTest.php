<?php

namespace Tests\Feature;

use App\Models\GoogleCalendarConnection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoogleCalendarSettingsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_settings_page_renders_when_not_connected(): void
    {
        $response = $this->actingAs($this->user)
            ->get('/settings/google-calendar');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('settings/google-calendar')
            ->where('connection.connected', false)
        );
    }

    public function test_settings_page_renders_when_connected(): void
    {
        GoogleCalendarConnection::factory()->create([
            'user_id' => $this->user->id,
            'calendar_id' => 'test@google.com',
            'connection_type' => 'oauth2',
            'enabled' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->get('/settings/google-calendar');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('settings/google-calendar')
            ->where('connection.connected', true)
            ->where('connection.calendar_id', 'test@google.com')
            ->where('connection.connection_type', 'oauth2')
            ->where('connection.enabled', true)
        );
    }

    public function test_can_disconnect_from_settings(): void
    {
        GoogleCalendarConnection::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->delete('/settings/google-calendar/disconnect');

        $response->assertRedirect('/settings/google-calendar');
        $this->assertDatabaseMissing('google_calendar_connections', [
            'user_id' => $this->user->id,
        ]);
    }

    public function test_settings_page_requires_authentication(): void
    {
        $response = $this->get('/settings/google-calendar');

        $response->assertRedirect('/login');
    }

    public function test_oauth_redirect_requires_authentication(): void
    {
        $response = $this->get('/settings/google-calendar/connect');

        $response->assertRedirect('/login');
    }
}
