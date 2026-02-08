<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoogleCalendarConnection extends Model
{
    /** @use HasFactory<\Database\Factories\GoogleCalendarConnectionFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'connection_type',
        'calendar_id',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'enabled',
        'sync_token',
        'last_synced_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'token_expires_at' => 'datetime',
            'enabled' => 'boolean',
            'last_synced_at' => 'datetime',
        ];
    }

    /**
     * The user who owns this connection.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Sync mappings for this connection.
     */
    public function syncMappings(): HasMany
    {
        return $this->hasMany(GoogleCalendarSyncMapping::class);
    }

    /**
     * Scope to only enabled connections.
     */
    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('enabled', true);
    }

    /**
     * Scope to get connection for a specific user.
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id);
    }

    /**
     * Check if this connection uses OAuth2.
     */
    public function isOAuth2(): bool
    {
        return $this->connection_type === 'oauth2';
    }

    /**
     * Check if this connection uses a service account.
     */
    public function isServiceAccount(): bool
    {
        return $this->connection_type === 'service_account';
    }

    /**
     * Check if the OAuth2 token has expired.
     */
    public function isTokenExpired(): bool
    {
        if (! $this->isOAuth2() || ! $this->token_expires_at) {
            return false;
        }

        return $this->token_expires_at->isPast();
    }
}
