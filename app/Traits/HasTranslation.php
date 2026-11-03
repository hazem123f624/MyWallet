<?php

namespace App\Traits;

/**
 * Add translatable support to any enum.
 *
 * Usage:
 *   enum MyEnum: string {
 *       use HasTranslation;
 *   }
 *
 *   MyEnum::toTranslatableArray(); // Returns [{key, value}] with translated labels
 */
trait HasTranslation
{
    /**
     * Get all cases as key/value pairs with translated values.
     */
    public static function toTranslatableArray(): array
    {
        return array_map(fn ($case) => [
            'key' => $case->value,
            'value' => __($case->translationKey()),
        ], static::cases());
    }

    /**
     * Build the full translation key for a case.
     * Format: {prefix}::messages.enums.{EnumName}.{value}
     */
    public function translationKey(): string
    {
        $enumName = class_basename(static::class);
        $prefix = static::translationPrefix();

        return "{$prefix}::messages.enums.{$enumName}.{$this->value}";
    }

    /**
     * Override in enum to set module prefix (e.g., 'wallet').
     */
    public static function translationPrefix(): string
    {
        return 'messages';
    }
}