<?php

namespace Modules\Settings\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\Settings\Models\Settings;

class ParentTypeRule implements ValidationRule
{
    public string $expectedParentType;

    public function __construct($expectedParentType)
    {
        $this->expectedParentType = $expectedParentType;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $parent = Settings::withTrashed()->find($value);

        // لو مفيش parent
        if (! $parent) {
            $fail('Parent setting does not exist.');
        }

        // لو الـtype مش نفس المتوقع
        if ($parent?->type !== $this->expectedParentType) {
            $fail("Invalid parent type. Expected: {$this->expectedParentType}");
        }
    }
}
