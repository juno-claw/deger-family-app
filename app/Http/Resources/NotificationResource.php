<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
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
            'type' => $this->type,
            'title' => $this->title,
            'message' => $this->message,
            'payload' => $this->data,
            'read_at' => $this->read_at,
            'from_user' => new UserResource($this->whenLoaded('fromUser')),
            'created_at' => $this->created_at,
        ];
    }
}
