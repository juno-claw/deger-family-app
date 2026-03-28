<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventReminderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'calendar_event_id' => $this->calendar_event_id,
            'user_id' => $this->user_id,
            'user' => new UserResource($this->whenLoaded('user')),
            'type' => $this->type,
            'remind_at' => $this->remind_at,
            'minutes_before' => $this->minutes_before,
            'sent_at' => $this->sent_at,
            'created_at' => $this->created_at,
        ];
    }
}
