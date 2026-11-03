<?php

namespace Modules\Settings\Http\Requests\Types\Create;

use App\Rules\ArabicLetters;
use App\Rules\EnglishLetters;
use Illuminate\Foundation\Http\FormRequest;

class CountryRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name_ar' => ['required', 'string', 'max:255', new ArabicLetters],
            'name_en' => ['required', 'string', 'max:255', new EnglishLetters],
            'is_active' => ['nullable', 'boolean'],
            'data' => ['required', 'array'],
            'data.code' => ['required', 'string', 'max:10'],
            'data.phone_code' => ['required', 'string', 'max:10'],
            'data.phone_length' => ['nullable', 'integer'],
            'data.currency' => ['nullable', 'string', 'max:10'],
            'data.currency_symbol' => ['nullable', 'string', 'max:10'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
