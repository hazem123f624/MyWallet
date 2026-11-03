<?php

namespace App\Services\ActivityLog\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityLogExportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'file_name' => $this['file_name'],
            'path' => $this['path'],
            'url' => $this['url'],
        ];
    }
}
