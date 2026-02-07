<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationWebTest extends TestCase
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

    public function test_notifications_page_loads_successfully(): void
    {
        $response = $this->get('/notifications');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('notifications/index')
            ->has('notifications')
        );
    }

    public function test_notifications_page_only_shows_own_notifications(): void
    {
        Notification::factory()->create(['user_id' => $this->user->id, 'title' => 'Mine']);
        Notification::factory()->create(['title' => 'Not mine']);

        $response = $this->get('/notifications');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('notifications.data', 1)
        );
    }

    // ── Mark as Read ──────────────────────────────────

    public function test_can_mark_notification_as_read(): void
    {
        $notification = Notification::factory()->create(['user_id' => $this->user->id]);

        $response = $this->patch("/notifications/{$notification->id}/read");

        $response->assertRedirect();
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_cannot_mark_other_users_notification_as_read(): void
    {
        $other = User::factory()->create();
        $notification = Notification::factory()->create(['user_id' => $other->id]);

        $response = $this->patch("/notifications/{$notification->id}/read");

        $response->assertForbidden();
    }

    // ── Mark All as Read ──────────────────────────────

    public function test_can_mark_all_notifications_as_read(): void
    {
        Notification::factory()->count(3)->create(['user_id' => $this->user->id]);

        $response = $this->post('/notifications/read-all');

        $response->assertRedirect();
        $this->assertEquals(
            0,
            Notification::where('user_id', $this->user->id)->whereNull('read_at')->count()
        );
    }

    // ── Unread Count ──────────────────────────────────

    public function test_unread_count_returns_json(): void
    {
        Notification::factory()->count(2)->create(['user_id' => $this->user->id]);
        Notification::factory()->read()->create(['user_id' => $this->user->id]);

        $response = $this->getJson('/notifications/unread-count');

        $response->assertOk();
        $response->assertJson(['count' => 2]);
    }

    // ── Guests ──────────────────────────────────────────

    public function test_guests_cannot_access_notifications(): void
    {
        auth()->logout();

        $response = $this->get('/notifications');

        $response->assertRedirect(route('login'));
    }
}
