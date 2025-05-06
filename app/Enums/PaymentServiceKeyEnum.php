<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class PaymentServiceKeyEnum extends Enum
{
    const VIP = 'vip';
    const PREMIUM = 'premium';
    const BUMP = 'bump';
    const COLOR_FRAME = 'color_frame';
    const LARGE_FRAME = 'large_frame';
    const ALL_IN_ONE = 'all_in_one';

    public static function getDescription($value): string
    {
        return match ($value) {
            self::VIP => 'VIP çək',
            self::PREMIUM => 'Premium',
            self::BUMP => 'İrəli çək',
            self::COLOR_FRAME => 'Rəngli çərçivə',
            self::LARGE_FRAME => 'Böyük çərçivə (x2)',
            self::ALL_IN_ONE => 'Hamsı birində',
            default => self::getKey($value),
        };
    }
}
