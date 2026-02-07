<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CalendarEventResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'start_at' => $this->start_at,
            'end_at' => $this->end_at,
            'all_day' => $this->all_day,
            'recurrence' => $this->recurrence,
            'color' => $this->color,
            'owner' => new UserResource($this->whenLoaded('owner')),
            'shared_with' => UserResource::collection($this->whenLoaded('sharedWith')),
            'created_at' => $this->created_at,
        ];
    }
}
