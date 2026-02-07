<?php

namespace App\Policies;

use App\Models\FamilyList;
use App\Models\User;

class FamilyListPolicy
{
    /**
     * Determine whether the user can view any lists.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the list.
     */
    public function view(User $user, FamilyList $familyList): bool
    {
        return $user->id === $familyList->owner_id
            || $familyList->sharedWith()->where('users.id', $user->id)->exists();
    }

    /**
     * Determine whether the user can create lists.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the list.
     */
    public function update(User $user, FamilyList $familyList): bool
    {
        if ($user->id === $familyList->owner_id) {
            return true;
        }

        return $familyList->sharedWith()
            ->where('users.id', $user->id)
            ->wherePivot('permission', 'edit')
            ->exists();
    }

    /**
     * Determine whether the user can delete the list.
     */
    public function delete(User $user, FamilyList $familyList): bool
    {
        return $user->id === $familyList->owner_id;
    }

    /**
     * Determine whether the user can share the list.
     */
    public function share(User $user, FamilyList $familyList): bool
    {
        return $user->id === $familyList->owner_id;
    }
}
