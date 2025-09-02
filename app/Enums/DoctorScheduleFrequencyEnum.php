<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class DoctorScheduleFrequencyEnum extends Enum
{
    const Daily = 'daily';
    const Weekly = 'weekly';
    const Monthly = 'monthly';

    public static function getDescription($value): string
    {
        return match ($value) {
            self::Daily => 'Günlük',
            self::Weekly => 'Həftəlik',
            self::Monthly => 'Aylıq',
            default => self::getKey($value),
        };
    }
}
