<?php

namespace Modules\Settings\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Settings\Models\Settings;

/**
 * @mixin Settings
 */
class SettingsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        

        $getFrom = isset($this->resource->relation) ? $this->resource->relation : $this->resource;

        return [
            'id'            => $getFrom->id,
            'type'          => $getFrom->type,
            'name_ar'       => $getFrom->name_ar,
            'name_en'       => $getFrom->name_en,
            'setting_id'    => $getFrom->setting_id,
            'is_active'     => (bool) $getFrom->is_active,
            'code'          => $getFrom->data['code'] ?? null,
            'phone_code'    => $getFrom->data['phone_code'] ?? null,
            'phone_length'  => $getFrom->data['phone_length'] ?? null,
            'cities'        => SettingsResource::collection(
                        $this->whenLoaded('cities')
                    ),
        ];
    }
}
