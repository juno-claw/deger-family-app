<?php

namespace Tests\Unit;

use App\Models\Notification;
use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramServiceTest extends TestCase
{
    use RefreshDatabase;

    private TelegramService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TelegramService;

        // Clear any real Telegram config to isolate tests
        config(['services.telegram.users' => []]);
    }

    public function test_sends_telegram_notification_to_configured_user(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $user = User::factory()->create();

        config([
            "services.telegram.users.{$user->id}" => [
                'bot_token' => 'test-bot-token',
                'chat_id' => '123456789',
            ],
        ]);

        $notification = Notification::factory()->create([
            'user_id' => $user->id,
            'type' => 'list_shared',
            'title' => 'Liste geteilt',
            'message' => 'Olli hat eine Liste mit dir geteilt',
            'data' => ['list_id' => 42],
        ]);

        $this->service->sendNotification($notification);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'bottest-bot-token/sendMessage')
                && $request['chat_id'] === '123456789'
                && $request['parse_mode'] === 'HTML'
                && str_contains($request['text'], 'Liste geteilt');
        });

        $this->assertNotNull($notification->fresh()->telegram_sent_at);
    }

    public function test_skips_user_without_telegram_config(): void
    {
        Http::fake();

        $user = User::factory()->create();

        $notification = Notification::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->service->sendNotification($notification);

        Http::assertNothingSent();
        $this->assertNull($notification->fresh()->telegram_sent_at);
    }

    public function test_skips_user_with_empty_bot_token(): void
    {
        Http::fake();

        $user = User::factory()->create();

        config([
            "services.telegram.users.{$user->id}" => [
                'bot_token' => '',
                'chat_id' => '123456789',
            ],
        ]);

        $notification = Notification::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->service->sendNotification($notification);

        Http::assertNothingSent();
        $this->assertNull($notification->fresh()->telegram_sent_at);
    }

    public function test_does_not_throw_on_telegram_api_failure(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => false, 'description' => 'Bad Request'], 400),
        ]);

        $user = User::factory()->create();

        config([
            "services.telegram.users.{$user->id}" => [
                'bot_token' => 'test-bot-token',
                'chat_id' => '123456789',
            ],
        ]);

        $notification = Notification::factory()->create([
            'user_id' => $user->id,
        ]);

        // Should not throw
        $this->service->sendNotification($notification);

        $this->assertNull($notification->fresh()->telegram_sent_at);
    }

    public function test_does_not_throw_on_network_error(): void
    {
        Http::fake([
            'api.telegram.org/*' => fn () => throw new \RuntimeException('Connection refused'),
        ]);

        $user = User::factory()->create();

        config([
            "services.telegram.users.{$user->id}" => [
                'bot_token' => 'test-bot-token',
                'chat_id' => '123456789',
            ],
        ]);

        $notification = Notification::factory()->create([
            'user_id' => $user->id,
        ]);

        // Should not throw
        $this->service->sendNotification($notification);

        $this->assertNull($notification->fresh()->telegram_sent_at);
    }

    public function test_build_message_includes_title_and_body(): void
    {
        $notification = Notification::factory()->make([
            'title' => 'Test Title',
            'message' => 'Test body message',
            'type' => 'general',
            'data' => [],
        ]);

        $message = $this->service->buildMessage($notification);

        $this->assertStringContainsString('<b>Test Title</b>', $message);
        $this->assertStringContainsString('Test body message', $message);
    }

    public function test_build_message_includes_url_for_list_shared(): void
    {
        $notification = Notification::factory()->make([
            'type' => 'list_shared',
            'data' => ['list_id' => 7],
        ]);

        $message = $this->service->buildMessage($notification);

        $expectedUrl = config('app.url').'/lists/7';
        $this->assertStringContainsString($expectedUrl, $message);
    }

    public function test_build_message_includes_url_for_event_shared(): void
    {
        $notification = Notification::factory()->make([
            'type' => 'event_shared',
            'data' => [],
        ]);

        $message = $this->service->buildMessage($notification);

        $expectedUrl = config('app.url').'/calendar';
        $this->assertStringContainsString($expectedUrl, $message);
    }

    public function test_build_message_includes_url_for_note_shared(): void
    {
        $notification = Notification::factory()->make([
            'type' => 'note_shared',
            'data' => ['note_id' => 3],
        ]);

        $message = $this->service->buildMessage($notification);

        $expectedUrl = config('app.url').'/notes/3';
        $this->assertStringContainsString($expectedUrl, $message);
    }

    public function test_build_message_escapes_html_in_title_and_body(): void
    {
        $notification = Notification::factory()->make([
            'title' => 'Test <script>alert("xss")</script>',
            'message' => 'Body with <b>tags</b> & "quotes"',
            'type' => 'general',
            'data' => [],
        ]);

        $message = $this->service->buildMessage($notification);

        $this->assertStringNotContainsString('<script>', $message);
        $this->assertStringContainsString('&lt;script&gt;', $message);
        $this->assertStringContainsString('&amp; &quot;quotes&quot;', $message);
    }

    public function test_get_notification_url_returns_null_for_unknown_type(): void
    {
        $notification = Notification::factory()->make([
            'type' => 'general',
            'data' => [],
        ]);

        $this->assertNull($this->service->getNotificationUrl($notification));
    }

    public function test_get_config_for_user_returns_null_for_unconfigured_user(): void
    {
        $this->assertNull($this->service->getConfigForUser(9999));
    }

    public function test_get_config_for_user_returns_config_when_set(): void
    {
        config([
            'services.telegram.users.5' => [
                'bot_token' => 'my-token',
                'chat_id' => '555',
            ],
        ]);

        $config = $this->service->getConfigForUser(5);

        $this->assertNotNull($config);
        $this->assertEquals('my-token', $config['bot_token']);
        $this->assertEquals('555', $config['chat_id']);
    }
}
