<?php

namespace Modules\Users\Resources;

use App\Services\CountryService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Users\Models\User;

/**
 * @mixin User
 *
 * @property int|null $country_id
 */
class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $countryId = data_get($this->resource, 'country_id');

        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'email'         => $this->email,
            'phone'         => $this->phone,
            'country_code'  => $countryId
                ? (app(CountryService::class)->getCountryById($countryId)?->data['phone_code'] ?? null)
                : null,
            'avatar'        => $this->avatar ? asset('storage/'.$this->avatar) : null,
            'postal_code'   => $this->postal_code,
            'main_address'  => $this->main_address,
            'is_active'     => (bool) $this->is_active,
            'created_at'    => $this->created_at?->format('Y-m-d'),
            'token'         => $this->token ?? null,
            'expires_in'    => $this->expires_in ?? null,
            'token_type'    => $this->token_type ?? 'Bearer',
            'last_login_at' => $this->last_login_at?->format('Y-m-d H:i:s'),
        ];
    }
}
