<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class PaymentServiceOptionTypeEnum extends Enum
{
    const Hour = 'hour';
    const Day = 'day';
    const Week = 'week';
    const Month = 'month';

    public static function getDescription($value): string
    {
        return match ($value) {
            self::Hour => 'Saat',
            self::Day => 'Gün',
            self::Week => 'Həftə',
            self::Month => 'Ay',
            default => self::getKey($value),
        };
    }
}
