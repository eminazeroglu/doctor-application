<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class NotificationPreferenceTypeEnum extends Enum
{
    const Appointment = 'appointment';
    const Review = 'review';
    const Message = 'message';
    const System = 'system';

    public static function getDescription($value): string
    {
        return match ($value) {
            self::Appointment => 'Randevu bildirişləri',
            self::Review => 'Rəy bildirişləri',
            self::Message => 'Mesaj bildirişləri',
            self::System => 'Sistem bildirişləri',
            default => self::getKey($value),
        };
    }
}
