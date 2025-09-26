<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class AppointmentDoctorCancelReason extends Enum
{
    const WRONG_APPOINTMENT = 'wrong_appointment';
    const SERVICE_NOT_AVAILABLE = 'service_not_available';
    const TIME_NOT_SUITABLE = 'time_not_suitable';
    const TECHNICAL_ADMINISTRATIVE = 'technical_administrative';
    const OTHER_REASON = 'other_reason';

    public static function getDescription($value): string
    {
        return match ($value) {
            self::WRONG_APPOINTMENT => 'Randevu səhv götürülüb',
            self::SERVICE_NOT_AVAILABLE => 'Göstərilən xidmət həmin tarixdə mümkün deyil',
            self::TIME_NOT_SUITABLE => 'Seçilmiş vaxt mənə uyğun deyil',
            self::TECHNICAL_ADMINISTRATIVE => 'Texniki/idarəvi səbəblərdən qəbul baş tutmur',
            self::OTHER_REASON => 'Digər səbəb',
            default => self::getKey($value),
        };
    }
}
