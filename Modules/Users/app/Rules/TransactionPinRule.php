<?php

namespace Modules\Users\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class TransactionPinRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $pin = (string) $value;

        if (! preg_match('/^\d{4}$/', $pin)) {
            $fail(__('users::messages.transaction_pin_format'));

            return;
        }

        if (preg_match('/^(\d)\1{3}$/', $pin)) {
            $fail(__('users::messages.transaction_pin_weak'));

            return;
        }

        $digits = array_map('intval', str_split($pin));
        $isAscending = true;
        $isDescending = true;

        for ($index = 1; $index < count($digits); $index++) {
            $difference = $digits[$index] - $digits[$index - 1];

            if ($difference !== 1) {
                $isAscending = false;
            }

            if ($difference !== -1) {
                $isDescending = false;
            }
        }

        if ($isAscending || $isDescending) {
            $fail(__('users::messages.transaction_pin_weak'));
        }
    }
}
