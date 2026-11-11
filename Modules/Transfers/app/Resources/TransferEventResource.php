<?php

namespace Modules\Transfers\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Transfers\Models\TransferEvent;

/**
 * @mixin TransferEvent
 */
class TransferEventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'from_status' => $this->from_status,
            'to_status' => $this->to_status,
            'provider_reference' => $this->provider_reference,
            'message' => $this->message,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
