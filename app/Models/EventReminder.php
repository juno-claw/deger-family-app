<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventReminder extends Model
{
    /** @use HasFactory<\Database\Factories\EventReminderFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'calendar_event_id',
        'user_id',
        'remind_at',
        'type',
        'minutes_before',
        'sent_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'remind_at' => 'datetime',
            'sent_at' => 'datetime',
            'minutes_before' => 'integer',
        ];
    }

    public function calendarEvent(): BelongsTo
    {
        return $this->belongsTo(CalendarEvent::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Recalculate remind_at from the event's start_at for relative reminders.
     */
    public function recalculateRemindAt(): void
    {
        if ($this->type !== 'relative' || $this->minutes_before === null) {
            return;
        }

        $this->remind_at = $this->calendarEvent->start_at->subMinutes($this->minutes_before);
        $this->save();
    }
}
