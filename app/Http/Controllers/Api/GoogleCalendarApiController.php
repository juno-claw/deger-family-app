<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GoogleCalendarConnection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class GoogleCalendarApiController extends Controller
{
    /**
     * Get the Google Calendar connection status for the authenticated user.
     */
    public function status(Request $request): JsonResponse
    {
        $connection = $request->user()->googleCalendarConnection;

        if (! $connection) {
            return response()->json([
                'connected' => false,
            ]);
        }

        return response()->json([
            'connected' => true,
            'connection_type' => $connection->connection_type,
            'calendar_id' => $connection->calendar_id,
            'enabled' => $connection->enabled,
            'last_synced_at' => $connection->last_synced_at?->toIso8601String(),
        ]);
    }

    /**
     * Connect via service account (for AI agents).
     */
    public function connect(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'calendar_id' => ['required', 'string', 'max:255'],
        ]);

        $connection = GoogleCalendarConnection::updateOrCreate(
            ['user_id' => $request->user()->id],
            [
                'connection_type' => 'service_account',
                'calendar_id' => $validated['calendar_id'],
                'enabled' => true,
            ]
        );

        return response()->json([
            'connected' => true,
            'connection_type' => $connection->connection_type,
            'calendar_id' => $connection->calendar_id,
            'enabled' => $connection->enabled,
        ], 201);
    }

    /**
     * Disconnect the Google Calendar connection.
     */
    public function disconnect(Request $request): Response
    {
        $connection = $request->user()->googleCalendarConnection;

        if ($connection) {
            $connection->syncMappings()->delete();
            $connection->delete();
        }

        return response()->noContent();
    }

    /**
     * Toggle the enabled state of the connection.
     */
    public function toggle(Request $request): JsonResponse
    {
        $connection = $request->user()->googleCalendarConnection;

        if (! $connection) {
            return response()->json(['message' => 'Keine Google Calendar Verbindung vorhanden.'], 404);
        }

        $connection->update(['enabled' => ! $connection->enabled]);

        return response()->json([
            'enabled' => $connection->enabled,
        ]);
    }
}
