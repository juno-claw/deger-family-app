<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FamilyList extends Model
{
    /** @use HasFactory<\Database\Factories\FamilyListFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'lists';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'type',
        'icon',
        'owner_id',
    ];

    /**
     * The owner of this list.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Items in this list.
     */
    public function items(): HasMany
    {
        return $this->hasMany(ListItem::class, 'list_id');
    }

    /**
     * Users this list is shared with.
     */
    public function sharedWith(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'list_user', 'list_id', 'user_id')
            ->withPivot('permission')
            ->withTimestamps();
    }

    /**
     * Scope to get lists accessible by a given user (owned or shared).
     */
    public function scopeAccessibleBy(Builder $query, User $user): Builder
    {
        return $query->where('owner_id', $user->id)
            ->orWhereHas('sharedWith', function (Builder $q) use ($user) {
                $q->where('users.id', $user->id);
            });
    }
}
