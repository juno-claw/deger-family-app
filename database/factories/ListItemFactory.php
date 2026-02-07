<?php

namespace Database\Factories;

use App\Models\FamilyList;
use App\Models\ListItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ListItem>
 */
class ListItemFactory extends Factory
{
    protected $model = ListItem::class;

    public function definition(): array
    {
        return [
            'list_id' => FamilyList::factory(),
            'content' => fake()->sentence(3),
            'is_completed' => false,
            'position' => 0,
            'created_by' => User::factory(),
        ];
    }

    /**
     * Mark the item as completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_completed' => true,
        ]);
    }
}
