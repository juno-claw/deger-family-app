<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCalendarEventRequest;
use App\Http\Requests\UpdateCalendarEventRequest;
use App\Models\CalendarEvent;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CalendarEventController extends Controller
{
    /**
     * Default color per user ID.
     */
    private const USER_COLORS = [
        1 => '#3b82f6', // Olli - blau
        2 => '#ec4899', // Sabsy - pink
        3 => '#22c55e', // Juno - gruen
    ];

    /**
     * Display a listing of calendar events for a given month.
     */
    public function index(Request $request)
    {
        $month = (int) $request->query('month', now()->month);
        $year = (int) $request->query('year', now()->year);

        $startOfMonth = \Carbon\Carbon::create($year, $month, 1)->startOfMonth();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        $events = CalendarEvent::accessibleBy(auth()->user())
            ->where(function ($query) use ($startOfMonth, $endOfMonth) {
                $query->whereBetween('start_at', [$startOfMonth, $endOfMonth])
                    ->orWhereBetween('end_at', [$startOfMonth, $endOfMonth])
                    ->orWhere(function ($q) use ($startOfMonth, $endOfMonth) {
                        $q->where('start_at', '<=', $startOfMonth)
                            ->where('end_at', '>=', $endOfMonth);
                    });
            })
            ->get();

        $events->load(['owner', 'sharedWith']);

        // Apply default color coding based on owner
        $events->each(function ($event) {
            if (is_null($event->color)) {
                $event->color = self::USER_COLORS[$event->owner_id] ?? '#6b7280';
            }
        });

        $users = User::where('id', '!=', auth()->id())->get(['id', 'name', 'email']);

        return Inertia::render('calendar/index', compact('events', 'users', 'month', 'year'));
    }

    /**
     * Store a newly created calendar event.
     */
    public function store(StoreCalendarEventRequest $request)
    {
        CalendarEvent::create(array_merge(
            $request->validated(),
            ['owner_id' => auth()->id()]
        ));

        return redirect()->back();
    }

    /**
     * Display the specified calendar event.
     */
    public function show(CalendarEvent $event)
    {
        $this->authorize('view', $event);

        $event->load(['owner', 'sharedWith']);

        return Inertia::render('calendar/show', compact('event'));
    }

    /**
     * Update the specified calendar event.
     */
    public function update(UpdateCalendarEventRequest $request, CalendarEvent $event)
    {
        $this->authorize('update', $event);

        $event->update($request->validated());

        return redirect()->back();
    }

    /**
     * Remove the specified calendar event.
     */
    public function destroy(CalendarEvent $event)
    {
        $this->authorize('delete', $event);

        $event->delete();

        return redirect()->back();
    }

    /**
     * Share the calendar event with another user.
     */
    public function share(Request $request, CalendarEvent $event)
    {
        $this->authorize('share', $event);

        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $event->sharedWith()->syncWithoutDetaching([
            $request->user_id => ['status' => 'pending'],
        ]);

        Notification::create([
            'user_id' => $request->user_id,
            'from_user_id' => auth()->id(),
            'type' => 'event_shared',
            'title' => 'Kalender-Einladung',
            'message' => auth()->user()->name . ' hat dich zu "' . $event->title . '" eingeladen.',
            'data' => ['event_id' => $event->id],
        ]);

        return redirect()->back();
    }
}
