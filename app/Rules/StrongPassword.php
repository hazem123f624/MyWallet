<?php

namespace App\Rules;

use App\Constants\RegexPatterns;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class StrongPassword implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $uppercase = preg_match(RegexPatterns::HAS_UPPERCASE, $value);
        $lowercase = preg_match(RegexPatterns::HAS_LOWERCASE, $value);
        $number = preg_match(RegexPatterns::HAS_NUMBER, $value);
        $specialChars = preg_match(RegexPatterns::HAS_SPECIAL_CHARACTER, $value);

        if (! $uppercase || ! $lowercase || ! $number || ! $specialChars || strlen($value) < 6) {
            $fail('Password must be at least 8 characters and include upper, lower, number, and special character.');
        }
    }
}
