<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCalendarEventRequest;
use App\Http\Requests\UpdateCalendarEventRequest;
use App\Models\CalendarEvent;
use App\Models\EventReminder;
use App\Models\User;
use App\Traits\HasSharing;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CalendarEventController extends Controller
{
    use HasSharing;

    /**
     * Default fallback color when no color is set on the event.
     */
    private const DEFAULT_COLOR = '#6b7280';

    /**
     * Display a listing of calendar events for a given month.
     */
    public function index(Request $request): Response
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

        $events->load(['owner', 'sharedWith', 'reminders.user']);

        // Apply default color if none is set
        $events->each(function ($event): void {
            if (is_null($event->color)) {
                $event->color = self::DEFAULT_COLOR;
            }
        });

        $users = User::where('id', '!=', auth()->id())->get(['id', 'name', 'email']);

        return Inertia::render('calendar/index', compact('events', 'users', 'month', 'year'));
    }

    /**
     * Store a newly created calendar event.
     */
    public function store(StoreCalendarEventRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $reminders = $validated['reminders'] ?? [];
        unset($validated['reminders']);

        $event = CalendarEvent::create(array_merge(
            $validated,
            ['owner_id' => auth()->id()]
        ));

        $this->syncReminders($event, $reminders);

        return redirect()->back();
    }

    /**
     * Display the specified calendar event.
     */
    public function show(CalendarEvent $event): Response
    {
        $this->authorize('view', $event);

        $event->load(['owner', 'sharedWith', 'reminders.user']);

        return Inertia::render('calendar/show', compact('event'));
    }

    /**
     * Update the specified calendar event.
     */
    public function update(UpdateCalendarEventRequest $request, CalendarEvent $event): RedirectResponse
    {
        $this->authorize('update', $event);

        $validated = $request->validated();
        $reminders = $validated['reminders'] ?? null;
        unset($validated['reminders']);

        $event->update($validated);

        if ($reminders !== null) {
            $event->reminders()->whereNull('sent_at')->delete();
            $this->syncReminders($event, $reminders);
        }

        return redirect()->back();
    }

    /**
     * Remove the specified calendar event.
     */
    public function destroy(CalendarEvent $event): RedirectResponse
    {
        $this->authorize('delete', $event);

        $event->delete();

        return redirect()->back();
    }

    /**
     * Share the calendar event with another user.
     */
    public function share(Request $request, CalendarEvent $event): RedirectResponse
    {
        return $this->performShare($request, $event);
    }

    /**
     * @param  array<int, array{type: string, minutes_before?: int|null, remind_at?: string|null, user_ids: list<int>}>  $reminders
     */
    private function syncReminders(CalendarEvent $event, array $reminders): void
    {
        foreach ($reminders as $reminderData) {
            $userIds = $reminderData['user_ids'] ?? [];
            foreach ($userIds as $userId) {
                $remindAt = $reminderData['type'] === 'relative' && ! empty($reminderData['minutes_before'])
                    ? $event->start_at->copy()->subMinutes((int) $reminderData['minutes_before'])
                    : Carbon::parse($reminderData['remind_at']);

                EventReminder::create([
                    'calendar_event_id' => $event->id,
                    'user_id' => $userId,
                    'type' => $reminderData['type'],
                    'minutes_before' => $reminderData['minutes_before'] ?? null,
                    'remind_at' => $remindAt,
                ]);
            }
        }
    }

    // ── HasSharing implementation ─────────────────────

    protected function sharingPivotField(): string
    {
        return 'status';
    }

    protected function sharingRequiresPermission(): bool
    {
        return false;
    }

    protected function sharingDefaultPermission(): string
    {
        return 'pending';
    }

    protected function sharingNotificationType(): string
    {
        return 'event_shared';
    }

    protected function sharingNotificationTitle(): string
    {
        return 'Kalender-Einladung';
    }

    protected function sharingNotificationMessage(Model $resource): string
    {
        return auth()->user()->name.' hat dich zu "'.$resource->title.'" eingeladen.';
    }

    protected function sharingNotificationData(Model $resource): array
    {
        return ['event_id' => $resource->id];
    }
}
