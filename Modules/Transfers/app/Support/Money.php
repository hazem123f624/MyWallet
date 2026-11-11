<?php

namespace Modules\Transfers\Support;

final class Money
{
    public static function normalize(mixed $value): string
    {
        return number_format((float) $value, 2, '.', '');
    }

    public static function add(mixed $left, mixed $right): string
    {
        return self::normalize((float) $left + (float) $right);
    }

    public static function isPositive(mixed $value): bool
    {
        return round((float) $value, 2) > 0;
    }

    public static function greaterThanOrEqual(mixed $left, mixed $right): bool
    {
        return round((float) $left - (float) $right, 2) >= 0;
    }
}
