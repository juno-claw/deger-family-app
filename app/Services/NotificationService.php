<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;

class NotificationService
{
    public function notify(User $user, ?User $fromUser, string $type, string $title, string $message, array $data = []): Notification
    {
        return Notification::create([
            'user_id' => $user->id,
            'from_user_id' => $fromUser?->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
        ]);
    }

    public function getUnreadCount(User $user): int
    {
        return Notification::where('user_id', $user->id)->unread()->count();
    }

    public function markAsRead(Notification $notification): void
    {
        $notification->update(['read_at' => now()]);
    }

    public function markAllAsRead(User $user): void
    {
        Notification::where('user_id', $user->id)->unread()->update(['read_at' => now()]);
    }
}
