<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class NotificationChannelEnum extends Enum
{
    const Email = 'email';
    const Sms = 'sms';
    const Push = 'push';
    const InApp = 'in_app';

    public static function getDescription($value): string
    {
        return match ($value) {
            self::Email => 'E-poçt',
            self::Sms => 'SMS',
            self::Push => 'Push bildiriş',
            self::InApp => 'Tətbiq daxili bildiriş',
            default => self::getKey($value),
        };
    }
}
