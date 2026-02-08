<?php

namespace App\Services;

use App\Models\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    /**
     * Send a notification to the user's Telegram bot.
     */
    public function sendNotification(Notification $notification): void
    {
        $config = $this->getConfigForUser($notification->user_id);

        if (! $config) {
            return;
        }

        $message = $this->buildMessage($notification);

        try {
            $response = Http::post(
                "https://api.telegram.org/bot{$config['bot_token']}/sendMessage",
                [
                    'chat_id' => $config['chat_id'],
                    'text' => $message,
                    'parse_mode' => 'HTML',
                    'disable_web_page_preview' => true,
                ]
            );

            if ($response->successful()) {
                $notification->update(['telegram_sent_at' => now()]);
            } else {
                Log::warning('Telegram notification failed', [
                    'notification_id' => $notification->id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Telegram notification error', [
                'notification_id' => $notification->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get Telegram config (bot_token + chat_id) for the given user ID.
     *
     * @return array{bot_token: string, chat_id: string}|null
     */
    public function getConfigForUser(int $userId): ?array
    {
        /** @var array{bot_token: ?string, chat_id: ?string}|null $config */
        $config = config("services.telegram.users.{$userId}");

        if (! $config || empty($config['bot_token']) || empty($config['chat_id'])) {
            return null;
        }

        return $config;
    }

    /**
     * Build the Telegram message text from a notification.
     */
    public function buildMessage(Notification $notification): string
    {
        $lines = [];
        $lines[] = "<b>{$this->escapeHtml($notification->title)}</b>";
        $lines[] = $this->escapeHtml($notification->message);

        $url = $this->getNotificationUrl($notification);
        if ($url) {
            $lines[] = '';
            $lines[] = "<a href=\"{$this->escapeHtml($url)}\">{$this->escapeHtml($url)}</a>";
        }

        return implode("\n", $lines);
    }

    /**
     * Build the full URL for a notification based on its type and data.
     */
    public function getNotificationUrl(Notification $notification): ?string
    {
        $baseUrl = config('app.url');
        $data = $notification->data ?? [];

        $path = match ($notification->type) {
            'list_shared' => isset($data['list_id']) ? "/lists/{$data['list_id']}" : null,
            'event_shared' => '/calendar',
            'note_shared' => isset($data['note_id']) ? "/notes/{$data['note_id']}" : null,
            'recipe_shared' => isset($data['recipe_id']) ? "/recipes/{$data['recipe_id']}" : null,
            default => null,
        };

        if (! $path) {
            return null;
        }

        return rtrim($baseUrl, '/').$path;
    }

    /**
     * Escape HTML special characters for Telegram HTML parse mode.
     */
    private function escapeHtml(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
