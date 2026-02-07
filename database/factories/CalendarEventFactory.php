<?php

namespace Database\Factories;

use App\Models\CalendarEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CalendarEvent>
 */
class CalendarEventFactory extends Factory
{
    protected $model = CalendarEvent::class;

    public function definition(): array
    {
        $startAt = fake()->dateTimeBetween('now', '+30 days');

        return [
            'title' => fake()->sentence(3),
            'description' => fake()->optional()->paragraph(),
            'start_at' => $startAt,
            'end_at' => fake()->optional()->dateTimeBetween($startAt, '+31 days'),
            'all_day' => fake()->boolean(30),
            'recurrence' => fake()->randomElement(['none', 'none', 'none', 'daily', 'weekly', 'monthly', 'yearly']),
            'color' => fake()->optional()->hexColor(),
            'owner_id' => User::factory(),
        ];
    }

    /**
     * Set the event as all-day.
     */
    public function allDay(): static
    {
        return $this->state(fn (array $attributes) => [
            'all_day' => true,
        ]);
    }

    /**
     * Set the event with a specific recurrence.
     */
    public function recurring(string $recurrence = 'weekly'): static
    {
        return $this->state(fn (array $attributes) => [
            'recurrence' => $recurrence,
        ]);
    }
}
