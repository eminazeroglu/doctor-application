<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class NotificationTemplateTypeEnum extends Enum
{
    const Appointment = 'appointment';
    const Review = 'review';
    const Message = 'message';
    const System = 'system';
    const User = 'user';

    public static function getDescription($value): string
    {
        return match ($value) {
            self::Appointment => 'Randevu',
            self::Review => 'Rəy',
            self::Message => 'Mesaj',
            self::System => 'Sistem',
            self::User => 'İstifadəçi',
            default => self::getKey($value),
        };
    }
}
