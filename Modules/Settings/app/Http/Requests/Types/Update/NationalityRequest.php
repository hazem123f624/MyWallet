<?php

namespace Modules\Settings\Http\Requests\Types\Update;

use App\Rules\ArabicLetters;
use App\Rules\EnglishLetters;
use Illuminate\Foundation\Http\FormRequest;

class NationalityRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'id' => 'required|exists:settings,id',
            'name_ar' => ['required', 'string', 'max:255', new ArabicLetters],
            'name_en' => ['required', 'string', 'max:255', new EnglishLetters],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
