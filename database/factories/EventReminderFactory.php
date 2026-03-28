<?php

namespace Database\Factories;

use App\Models\CalendarEvent;
use App\Models\EventReminder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventReminder>
 */
class EventReminderFactory extends Factory
{
    protected $model = EventReminder::class;

    public function definition(): array
    {
        $event = CalendarEvent::factory()->create();

        return [
            'calendar_event_id' => $event->id,
            'user_id' => $event->owner_id,
            'type' => 'relative',
            'minutes_before' => fake()->randomElement([15, 30, 60, 120, 1440, 2880]),
            'remind_at' => $event->start_at->subMinutes(60),
            'sent_at' => null,
        ];
    }

    public function absolute(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'absolute',
            'minutes_before' => null,
        ]);
    }

    public function relative(int $minutesBefore = 60): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'relative',
            'minutes_before' => $minutesBefore,
        ]);
    }

    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'sent_at' => now(),
        ]);
    }
}
