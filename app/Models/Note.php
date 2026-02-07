<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Note extends Model
{
    /** @use HasFactory<\Database\Factories\NoteFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'content',
        'owner_id',
        'is_pinned',
        'color',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_pinned' => 'boolean',
        ];
    }

    /**
     * The owner of this note.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Users this note is shared with.
     */
    public function sharedWith(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'note_user')
            ->withPivot('permission')
            ->withTimestamps();
    }

    /**
     * Scope to get notes accessible by a given user (owned or shared).
     */
    public function scopeAccessibleBy(Builder $query, User $user): Builder
    {
        return $query->where('owner_id', $user->id)
            ->orWhereHas('sharedWith', function (Builder $q) use ($user) {
                $q->where('users.id', $user->id);
            });
    }
}
