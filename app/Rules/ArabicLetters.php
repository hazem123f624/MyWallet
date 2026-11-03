<?php

namespace App\Rules;

use App\Constants\RegexPatterns;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * The value must start with an Arabic letter and may then contain Arabic
 * or Latin letters, digits, spaces, underscores, hyphens and standard
 * punctuation (so texts such as "دور جديد" or an Arabic description that
 * mentions English terms are accepted). Used parameterless: `new ArabicLetters`.
 */
class ArabicLetters implements ValidationRule
{
    protected string $pattern = RegexPatterns::ARABIC_LEADING_TEXT;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! preg_match($this->pattern, $value)) {
            $fail(__('invalid Arabic text'));
        }
    }
}
