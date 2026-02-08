<?php

namespace Database\Factories;

use App\Models\Recipe;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Recipe>
 */
class RecipeFactory extends Factory
{
    protected $model = Recipe::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'description' => fake()->optional()->sentence(),
            'category' => fake()->randomElement(['cooking', 'baking', 'dessert', 'snack', 'drink']),
            'servings' => fake()->numberBetween(1, 8),
            'prep_time' => fake()->optional()->numberBetween(5, 60),
            'cook_time' => fake()->optional()->numberBetween(10, 120),
            'ingredients' => implode("\n", fake()->sentences(fake()->numberBetween(3, 8))),
            'instructions' => implode("\n", fake()->paragraphs(fake()->numberBetween(2, 5))),
            'owner_id' => User::factory(),
            'is_favorite' => false,
        ];
    }

    /**
     * Mark the recipe as favorite.
     */
    public function favorite(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_favorite' => true,
        ]);
    }

    /**
     * Set the category to baking.
     */
    public function baking(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'baking',
        ]);
    }

    /**
     * Set the category to cooking.
     */
    public function cooking(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'cooking',
        ]);
    }
}
