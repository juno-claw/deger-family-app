<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCalendarEventRequest;
use App\Http\Requests\UpdateCalendarEventRequest;
use App\Http\Resources\CalendarEventResource;
use App\Models\CalendarEvent;
use App\Traits\HasApiSharing;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class CalendarEventApiController extends Controller
{
    use HasApiSharing;

    /**
     * Display a listing of calendar events, filtered by date range or month/year.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = CalendarEvent::accessibleBy(auth()->user());

        if ($request->has('date_from') && $request->has('date_to')) {
            $dateFrom = Carbon::parse($request->query('date_from'))->startOfDay();
            $dateTo = Carbon::parse($request->query('date_to'))->endOfDay();
        } else {
            $month = (int) $request->query('month', now()->month);
            $year = (int) $request->query('year', now()->year);

            $dateFrom = Carbon::create($year, $month, 1)->startOfMonth();
            $dateTo = $dateFrom->copy()->endOfMonth();
        }

        $events = $query->where(function ($q) use ($dateFrom, $dateTo) {
            $q->whereBetween('start_at', [$dateFrom, $dateTo])
                ->orWhereBetween('end_at', [$dateFrom, $dateTo])
                ->orWhere(function ($q) use ($dateFrom, $dateTo) {
                    $q->where('start_at', '<=', $dateFrom)
                        ->where('end_at', '>=', $dateTo);
                });
        })
            ->with(['owner', 'sharedWith'])
            ->get();

        return CalendarEventResource::collection($events);
    }

    /**
     * Store a newly created calendar event.
     */
    public function store(StoreCalendarEventRequest $request): CalendarEventResource
    {
        $event = CalendarEvent::create([
            ...$request->validated(),
            'owner_id' => auth()->id(),
        ]);

        $event->load(['owner', 'sharedWith']);

        return new CalendarEventResource($event);
    }

    /**
     * Display the specified calendar event.
     */
    public function show(CalendarEvent $event): CalendarEventResource
    {
        $this->authorize('view', $event);

        $event->load(['owner', 'sharedWith']);

        return new CalendarEventResource($event);
    }

    /**
     * Update the specified calendar event.
     */
    public function update(UpdateCalendarEventRequest $request, CalendarEvent $event): CalendarEventResource
    {
        $this->authorize('update', $event);

        $event->update($request->validated());

        $event->load(['owner', 'sharedWith']);

        return new CalendarEventResource($event);
    }

    /**
     * Remove the specified calendar event.
     */
    public function destroy(CalendarEvent $event): Response
    {
        $this->authorize('delete', $event);

        $event->delete();

        return response()->noContent();
    }

    /**
     * Share the calendar event with another user.
     */
    public function share(Request $request, CalendarEvent $event): CalendarEventResource
    {
        return $this->performApiShare($request, $event);
    }

    /**
     * Remove sharing of the calendar event from a user.
     */
    public function unshare(Request $request, CalendarEvent $event): CalendarEventResource
    {
        return $this->performApiUnshare($request, $event);
    }

    // ── HasApiSharing implementation ─────────────────────

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

    protected function sharingResource(Model $resource): CalendarEventResource
    {
        return new CalendarEventResource($resource);
    }
}
