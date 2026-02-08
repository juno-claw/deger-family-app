<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Recipe extends Model
{
    /** @use HasFactory<\Database\Factories\RecipeFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'description',
        'category',
        'servings',
        'prep_time',
        'cook_time',
        'ingredients',
        'instructions',
        'owner_id',
        'is_favorite',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_favorite' => 'boolean',
            'servings' => 'integer',
            'prep_time' => 'integer',
            'cook_time' => 'integer',
        ];
    }

    /**
     * The owner of this recipe.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Users this recipe is shared with.
     */
    public function sharedWith(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'recipe_user')
            ->withPivot('permission')
            ->withTimestamps();
    }

    /**
     * Scope to get recipes accessible by a given user (owned or shared).
     */
    public function scopeAccessibleBy(Builder $query, User $user): Builder
    {
        return $query->where('owner_id', $user->id)
            ->orWhereHas('sharedWith', function (Builder $q) use ($user) {
                $q->where('users.id', $user->id);
            });
    }
}
