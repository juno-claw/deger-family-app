<?php

namespace Database\Factories;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Notification>
 */
class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'from_user_id' => User::factory(),
            'type' => fake()->randomElement(['general', 'note_shared', 'list_shared', 'event_shared']),
            'title' => fake()->sentence(3),
            'message' => fake()->sentence(6),
            'data' => [],
            'read_at' => null,
        ];
    }

    /**
     * Mark the notification as read.
     */
    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => now(),
        ]);
    }
}
