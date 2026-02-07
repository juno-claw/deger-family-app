<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CalendarEvent extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'description',
        'start_at',
        'end_at',
        'all_day',
        'recurrence',
        'color',
        'owner_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'all_day' => 'boolean',
        ];
    }

    /**
     * The owner of this calendar event.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Users this event is shared with.
     */
    public function sharedWith(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'calendar_event_user')
            ->withPivot('status')
            ->withTimestamps();
    }

    /**
     * Scope to get events accessible by a given user (owned or shared).
     */
    public function scopeAccessibleBy(Builder $query, User $user): Builder
    {
        return $query->where('owner_id', $user->id)
            ->orWhereHas('sharedWith', function (Builder $q) use ($user) {
                $q->where('users.id', $user->id);
            });
    }
}
