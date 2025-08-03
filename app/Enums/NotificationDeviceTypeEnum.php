<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class NotificationDeviceTypeEnum extends Enum
{
    const Ios = 'ios';
    const Android = 'android';
    const Web = 'web';

    public static function getDescription($value): string
    {
        return match ($value) {
            self::Ios => 'iOS',
            self::Android => 'Android',
            self::Web => 'Web',
            default => self::getKey($value),
        };
    }
}
