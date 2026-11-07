<?php

namespace Modules\BankAccounts\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\BankAccounts\Models\Bank;

/**
 * @mixin Bank
 */
class BankResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $locale = app()->getLocale();

        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $locale === 'ar' ? $this->name_ar : $this->name_en,
            'name_en' => $this->name_en,
            'name_ar' => $this->name_ar,
            'logo' => $this->logo ? asset('storage/'.$this->logo) : null,
            'is_active' => (bool) $this->is_active,
            'supports_linking' => (bool) $this->supports_linking,
        ];
    }
}
