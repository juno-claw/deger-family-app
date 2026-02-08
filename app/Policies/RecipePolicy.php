<?php

namespace App\Policies;

use App\Models\Recipe;
use App\Models\User;

class RecipePolicy
{
    /**
     * Determine whether the user can view any recipes.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the recipe.
     */
    public function view(User $user, Recipe $recipe): bool
    {
        return $recipe->owner_id === $user->id
            || $recipe->sharedWith()->where('users.id', $user->id)->exists();
    }

    /**
     * Determine whether the user can create recipes.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the recipe.
     */
    public function update(User $user, Recipe $recipe): bool
    {
        if ($recipe->owner_id === $user->id) {
            return true;
        }

        return $recipe->sharedWith()
            ->where('users.id', $user->id)
            ->wherePivot('permission', 'edit')
            ->exists();
    }

    /**
     * Determine whether the user can delete the recipe.
     */
    public function delete(User $user, Recipe $recipe): bool
    {
        return $recipe->owner_id === $user->id;
    }

    /**
     * Determine whether the user can share the recipe.
     */
    public function share(User $user, Recipe $recipe): bool
    {
        return $recipe->owner_id === $user->id;
    }
}
