<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function index(): Response
    {
        $notifications = Notification::where('user_id', auth()->id())
            ->with('fromUser')
            ->latest()
            ->paginate(30);

        return Inertia::render('notifications/index', [
            'notifications' => $notifications,
        ]);
    }

    public function markAsRead(Notification $notification): RedirectResponse
    {
        if ($notification->user_id !== auth()->id()) {
            abort(403);
        }

        app(NotificationService::class)->markAsRead($notification);

        return back();
    }

    public function markAllAsRead(): RedirectResponse
    {
        app(NotificationService::class)->markAllAsRead(auth()->user());

        return back();
    }

    public function unreadCount(): JsonResponse
    {
        $count = app(NotificationService::class)->getUnreadCount(auth()->user());

        return response()->json(['count' => $count]);
    }
}
