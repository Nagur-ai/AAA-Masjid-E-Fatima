<?php

namespace App\Services\Finance;

/**
 * Amounts are handled as 2-decimal strings with bcmath to avoid float rounding.
 */
final class Money
{
    public static function normalize(string|int|float|null $value): string
    {
        if ($value === null || $value === '') {
            return '0.00';
        }

        if (is_float($value)) {
            $value = sprintf('%.2f', $value);
        }

        return bcadd((string) $value, '0', 2);
    }

    public static function format(string|int|float|null $value, string $currency): string
    {
        return $currency.' '.number_format((float) self::normalize($value), 2);
    }
}
