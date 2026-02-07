<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NotificationApiController extends Controller
{
    /**
     * Display a paginated listing of the authenticated user's notifications.
     */
    public function index(): AnonymousResourceCollection
    {
        $notifications = Notification::where('user_id', auth()->id())
            ->with('fromUser')
            ->latest()
            ->paginate(30);

        return NotificationResource::collection($notifications);
    }

    /**
     * Push a notification to another user.
     *
     * Only users with the 'ai_agent' role are allowed to push notifications.
     */
    public function push(Request $request): NotificationResource
    {
        if (! auth()->user()->isAiAgent()) {
            abort(403, 'Only AI agents may push notifications.');
        }

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:2000',
            'type' => 'sometimes|string|max:50',
            'data' => 'sometimes|array',
        ]);

        $notification = app(NotificationService::class)->notify(
            \App\Models\User::findOrFail($validated['user_id']),
            auth()->user(),
            $validated['type'] ?? 'general',
            $validated['title'],
            $validated['message'],
            $validated['data'] ?? [],
        );

        $notification->load('fromUser');

        return new NotificationResource($notification);
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead(Notification $notification): JsonResponse
    {
        if ($notification->user_id !== auth()->id()) {
            abort(403);
        }

        app(NotificationService::class)->markAsRead($notification);

        return response()->json(['message' => 'Notification marked as read.']);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(): JsonResponse
    {
        app(NotificationService::class)->markAllAsRead(auth()->user());

        return response()->json(['message' => 'All notifications marked as read.']);
    }
}
