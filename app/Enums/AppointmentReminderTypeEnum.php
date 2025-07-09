<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class AppointmentReminderTypeEnum extends Enum
{
    const Email = 'email';
    const Sms = 'sms';
    const Push = 'push';

    public static function getDescription($value): string
    {
        return match ($value) {
            self::Email => 'Email',
            self::Sms => 'Sms',
            self::Push => 'Push',
            default => self::getKey($value),
        };
    }
}
