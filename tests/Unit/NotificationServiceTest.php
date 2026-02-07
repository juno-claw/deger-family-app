<?php

namespace Tests\Unit;

use App\Models\Notification;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    private NotificationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new NotificationService;
    }

    public function test_notify_creates_notification(): void
    {
        $user = User::factory()->create();
        $fromUser = User::factory()->create();

        $notification = $this->service->notify(
            $user,
            $fromUser,
            'general',
            'Test Title',
            'Test message',
            ['key' => 'value']
        );

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'user_id' => $user->id,
            'from_user_id' => $fromUser->id,
            'type' => 'general',
            'title' => 'Test Title',
            'message' => 'Test message',
        ]);
        $this->assertEquals(['key' => 'value'], $notification->data);
    }

    public function test_notify_works_without_from_user(): void
    {
        $user = User::factory()->create();

        $notification = $this->service->notify(
            $user,
            null,
            'system',
            'System',
            'System message'
        );

        $this->assertNull($notification->from_user_id);
    }

    public function test_get_unread_count(): void
    {
        $user = User::factory()->create();
        Notification::factory()->count(3)->create(['user_id' => $user->id]);
        Notification::factory()->read()->count(2)->create(['user_id' => $user->id]);

        $this->assertEquals(3, $this->service->getUnreadCount($user));
    }

    public function test_mark_as_read(): void
    {
        $notification = Notification::factory()->create();

        $this->assertNull($notification->read_at);

        $this->service->markAsRead($notification);

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_mark_all_as_read(): void
    {
        $user = User::factory()->create();
        Notification::factory()->count(3)->create(['user_id' => $user->id]);

        $this->service->markAllAsRead($user);

        $this->assertEquals(0, Notification::where('user_id', $user->id)->whereNull('read_at')->count());
    }
}
