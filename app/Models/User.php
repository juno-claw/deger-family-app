<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * Lists owned by this user.
     */
    public function lists(): HasMany
    {
        return $this->hasMany(FamilyList::class, 'owner_id');
    }

    /**
     * Lists shared with this user.
     */
    public function sharedLists(): BelongsToMany
    {
        return $this->belongsToMany(FamilyList::class, 'list_user', 'user_id', 'list_id')
            ->withPivot('permission')
            ->withTimestamps();
    }

    /**
     * Calendar events owned by this user.
     */
    public function calendarEvents(): HasMany
    {
        return $this->hasMany(CalendarEvent::class, 'owner_id');
    }

    /**
     * Calendar events shared with this user.
     */
    public function sharedCalendarEvents(): BelongsToMany
    {
        return $this->belongsToMany(CalendarEvent::class, 'calendar_event_user')
            ->withPivot('status')
            ->withTimestamps();
    }

    /**
     * Notes owned by this user.
     */
    public function notes(): HasMany
    {
        return $this->hasMany(Note::class, 'owner_id');
    }

    /**
     * Notes shared with this user.
     */
    public function sharedNotes(): BelongsToMany
    {
        return $this->belongsToMany(Note::class, 'note_user')
            ->withPivot('permission')
            ->withTimestamps();
    }

    /**
     * Notifications for this user.
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Check if the user is an AI agent.
     */
    public function isAiAgent(): bool
    {
        return $this->role === 'ai_agent';
    }
}
