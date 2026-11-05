<?php

namespace Modules\Users\Http\Requests;

use App\Constants\RegexPatterns;
use App\Traits\HasPhoneValidation;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    use HasPhoneValidation;

    protected function prepareForValidation()
    {
        if (! array_key_exists('delete_avatar', $this->all())) {
            $this->merge(['delete_avatar' => 0]);
        }

        if ($this->filled('country_code') && $this->filled('phone')) {
            $countryId = $this->getCountryIdFromCode($this->country_code);

            if ($countryId) {
                $this->merge([
                    'country_id' => $countryId,
                    'full_phone' => $this->getFullPhoneNumber($this->phone, $this->country_code),
                ]);
            }
        }
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $userId = auth('sanctum')->id() ?? 0;

        return [
            'name'         => 'sometimes|string|max:255',
            'email'        => 'sometimes|email|unique:users,email,'.$userId,
            'country_code' => ['required_with:phone', 'nullable', 'string',

                function ($attribute, $value, $fail): void {
                    if (is_null($value) || $value === '') {
                        return;
                    }

                    if (! $this->isValidCountryCode($value)) {
                        $fail('Invalid country code selected.');
                    }
                },],


            'country_id'   => 'sometimes|exists:settings,id',
            'phone'        => ['sometimes', 'nullable', 'regex:'.RegexPatterns::PHONE_DIGITS, 'max:11',


                function ($attribute, $value, $fail): void {
                    if (is_null($value) || $value === '') {
                        return;
                    }

                    if ($this->filled('country_code')) {
                        try {
                            $this->validatePhoneNumber($value, $this->country_code);
                        } catch (\Exception $e) {
                            $fail($e->getMessage());
                        }
                    }
                },],


            'full_phone'   => ['sometimes', 'nullable', 'string', 'unique:users,phone,'.$userId],
            'avatar'        => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
            'delete_avatar' => 'sometimes|boolean',
            'postal_code'   => 'sometimes|string|max:20',
            'main_address'  => 'sometimes|string',
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
}
