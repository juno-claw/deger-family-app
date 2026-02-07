<?php

namespace App\Policies;

use App\Models\Note;
use App\Models\User;

class NotePolicy
{
    /**
     * Determine whether the user can view any notes.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the note.
     */
    public function view(User $user, Note $note): bool
    {
        return $note->owner_id === $user->id
            || $note->sharedWith()->where('users.id', $user->id)->exists();
    }

    /**
     * Determine whether the user can create notes.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the note.
     */
    public function update(User $user, Note $note): bool
    {
        if ($note->owner_id === $user->id) {
            return true;
        }

        return $note->sharedWith()
            ->where('users.id', $user->id)
            ->wherePivot('permission', 'edit')
            ->exists();
    }

    /**
     * Determine whether the user can delete the note.
     */
    public function delete(User $user, Note $note): bool
    {
        return $note->owner_id === $user->id;
    }

    /**
     * Determine whether the user can share the note.
     */
    public function share(User $user, Note $note): bool
    {
        return $note->owner_id === $user->id;
    }
}
