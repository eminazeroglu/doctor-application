<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class NotificationPriorityEnum extends Enum
{
    const LOW = 'low';
    const NORMAL = 'normal';
    const HIGH = 'high';

    public static function getDescription($value): string
    {
        return match ($value) {
            self::LOW => t('enums.notification_priority.low'),
            self::NORMAL => t('enums.notification_priority.normal'),
            self::HIGH => t('enums.notification_priority.high'),
            default => self::getKey($value),
        };
    }
}
