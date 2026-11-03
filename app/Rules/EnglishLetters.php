<?php

namespace App\Rules;

use App\Constants\RegexPatterns;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * The value must start with a Latin letter and may then contain Latin letters,
 * digits, spaces, underscores or hyphens (so human-readable names such as
 * "New Role" are accepted). Used parameterless: `new EnglishLetters`.
 */
class EnglishLetters implements ValidationRule
{
    protected string $pattern = RegexPatterns::ENGLISH_LEADING_TEXT;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! preg_match($this->pattern, $value)) {
            $fail(__('invalid English text'));
        }
    }
}
