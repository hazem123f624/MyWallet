<?php

namespace Modules\Settings\Http\Requests\Types\Update;

use App\Rules\ArabicLetters;
use App\Rules\EnglishLetters;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Settings\Enums\SettingsType;
use Modules\Settings\Rules\ParentTypeRule;

class CityRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'id' => 'required|exists:settings,id',
            'name_ar' => ['required', 'string', 'max:255', new ArabicLetters],
            'name_en' => ['required', 'string', 'max:255', new EnglishLetters],

            'parent_id' => ['required', 'exists:settings,id', new ParentTypeRule(SettingsType::COUNTRY->value)],

            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
