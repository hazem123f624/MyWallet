<?php

namespace App\Services\ActivityLog\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityLogResource extends JsonResource
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
            'log_type' => $this->log_type,
            'action' => $this->action,
            'description' => $this->description,
            'subject' => [
                'type' => $this->subject_type,
                'id' => $this->subject_id,
            ],
            'causer' => [
                'type' => $this->causer_type,
                'id' => $this->causer_id,
                'name' => $this->causer?->name ?? 'System',
            ],
            'properties' => $this->properties, // Diff or extra data
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
