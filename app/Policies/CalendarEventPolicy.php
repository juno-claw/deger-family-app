<?php

namespace App\Policies;

use App\Models\CalendarEvent;
use App\Models\User;

class CalendarEventPolicy
{
    /**
     * Determine whether the user can view any calendar events.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the calendar event.
     */
    public function view(User $user, CalendarEvent $event): bool
    {
        return $user->id === $event->owner_id
            || $event->sharedWith()->where('users.id', $user->id)->exists();
    }

    /**
     * Determine whether the user can create calendar events.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the calendar event.
     */
    public function update(User $user, CalendarEvent $event): bool
    {
        return $user->id === $event->owner_id
            || $event->sharedWith()
                ->where('users.id', $user->id)
                ->wherePivot('status', 'accepted')
                ->exists();
    }

    /**
     * Determine whether the user can delete the calendar event.
     */
    public function delete(User $user, CalendarEvent $event): bool
    {
        return $user->id === $event->owner_id;
    }

    /**
     * Determine whether the user can share the calendar event.
     */
    public function share(User $user, CalendarEvent $event): bool
    {
        return $user->id === $event->owner_id;
    }
}
