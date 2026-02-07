<?php

namespace Database\Factories;

use App\Models\FamilyList;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FamilyList>
 */
class FamilyListFactory extends Factory
{
    protected $model = FamilyList::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(2),
            'type' => fake()->randomElement(['todo', 'shopping']),
            'icon' => null,
            'owner_id' => User::factory(),
        ];
    }

    /**
     * Set the list type to shopping.
     */
    public function shopping(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'shopping',
        ]);
    }

    /**
     * Set the list type to todo.
     */
    public function todo(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'todo',
        ]);
    }
}
