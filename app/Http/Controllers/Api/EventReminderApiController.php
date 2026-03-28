<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEventReminderRequest;
use App\Http\Requests\UpdateEventReminderRequest;
use App\Http\Resources\EventReminderResource;
use App\Models\CalendarEvent;
use App\Models\EventReminder;
use Carbon\CarbonInterface;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class EventReminderApiController extends Controller
{
    public function index(CalendarEvent $event): AnonymousResourceCollection
    {
        $this->authorize('view', $event);

        return EventReminderResource::collection(
            $event->reminders()->with('user')->get()
        );
    }

    public function store(StoreEventReminderRequest $request, CalendarEvent $event): EventReminderResource
    {
        $this->authorize('update', $event);

        $data = $request->validated();
        $data['calendar_event_id'] = $event->id;
        $data['remind_at'] = $this->calculateRemindAt($data, $event);

        $reminder = EventReminder::create($data);
        $reminder->load('user');

        return new EventReminderResource($reminder);
    }

    public function update(UpdateEventReminderRequest $request, CalendarEvent $event, EventReminder $reminder): EventReminderResource
    {
        $this->authorize('update', $event);

        $data = $request->validated();

        $type = $data['type'] ?? $reminder->type;
        $minutesBefore = $data['minutes_before'] ?? $reminder->minutes_before;
        $remindAt = $data['remind_at'] ?? $reminder->remind_at;

        $data['remind_at'] = $this->calculateRemindAt([
            'type' => $type,
            'minutes_before' => $minutesBefore,
            'remind_at' => $remindAt,
        ], $event);

        $reminder->update($data);
        $reminder->load('user');

        return new EventReminderResource($reminder);
    }

    public function destroy(CalendarEvent $event, EventReminder $reminder): Response
    {
        $this->authorize('update', $event);

        $reminder->delete();

        return response()->noContent();
    }

    /**
     * @param  array{type: string, minutes_before?: int|null, remind_at?: string|null}  $data
     */
    private function calculateRemindAt(array $data, CalendarEvent $event): CarbonInterface
    {
        if ($data['type'] === 'relative' && ! empty($data['minutes_before'])) {
            return $event->start_at->copy()->subMinutes((int) $data['minutes_before']);
        }

        return \Carbon\Carbon::parse($data['remind_at']);
    }
}
