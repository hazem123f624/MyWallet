<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class KsaPhoneNumber implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $phone = preg_replace('/[^0-9]/', '', $value);

        // KSA phone number patterns:
        // 05xxxxxxxx (10 digits starting with 05)
        // 5xxxxxxxx (9 digits starting with 5)
        // +9665xxxxxxxx (with country code)
        // 009665xxxxxxxx (with double zero)
        // 9665xxxxxxxx (without plus)

        $patterns = [
            '/^(05)[0-9]{8}$/',           // 05XXXXXXXX (10 digits)
            '/^(5)[0-9]{8}$/',            // 5XXXXXXXX (9 digits)
            '/^(\+9665)[0-9]{8}$/',       // +9665XXXXXXXX
            '/^(009665)[0-9]{8}$/',       // 009665XXXXXXXX
            '/^(9665)[0-9]{8}$/',         // 9665XXXXXXXX
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $phone)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The :attribute must be a valid Saudi phone number.';
    }
}
