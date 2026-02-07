<?php

namespace Database\Factories;

use App\Models\Note;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Note>
 */
class NoteFactory extends Factory
{
    protected $model = Note::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'content' => fake()->optional()->paragraph(),
            'owner_id' => User::factory(),
            'is_pinned' => false,
            'color' => null,
        ];
    }

    /**
     * Mark the note as pinned.
     */
    public function pinned(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_pinned' => true,
        ]);
    }

    /**
     * Set a background color.
     */
    public function withColor(string $color = '#fef3c7'): static
    {
        return $this->state(fn (array $attributes) => [
            'color' => $color,
        ]);
    }
}
