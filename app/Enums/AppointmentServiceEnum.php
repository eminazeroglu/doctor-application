<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class AppointmentServiceEnum extends Enum
{
    const Pending = 'pending';
    const Confirmed = 'confirmed';
    const Cancelled = 'cancelled';
    const Completed = 'completed';
    const NoShow = 'no-show';

    public static function getDescription($value): string
    {
        return match ($value) {
            self::Pending => 'Gözləmədə',
            self::Confirmed => 'Təsdiqləndi',
            self::Cancelled => 'Ləğv edildi',
            self::Completed => 'Tamamlandı',
            self::NoShow => 'Gəlmədi',
            default => self::getKey($value),
        };
    }
}
