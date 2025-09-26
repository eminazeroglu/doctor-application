<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class AppointmentUserCancelReason extends Enum
{
    const WRONG_APPOINTMENT = 'wrong_appointment';
    const TIME_NOT_SUITABLE = 'time_not_suitable';
    const HEALTH_CONDITION_CHANGED = 'health_condition_changed';
    const VISITED_ANOTHER_DOCTOR = 'visited_another_doctor';
    const CANNOT_COME_TO_CLINIC = 'cannot_come_to_clinic';
    const OTHER_REASON = 'other_reason';

    public static function getDescription($value): string
    {
        return match ($value) {
            self::WRONG_APPOINTMENT => 'Randevu səhv götürülüb',
            self::TIME_NOT_SUITABLE => 'Seçilmiş vaxt mənə uyğun deyil',
            self::HEALTH_CONDITION_CHANGED => 'Sağlamlıq vəziyyətim dəyişib',
            self::VISITED_ANOTHER_DOCTOR => 'Başqa həkimə müraciət etmişəm',
            self::CANNOT_COME_TO_CLINIC => 'Klinikaya gələ bilmirəm',
            self::OTHER_REASON => 'Digər səbəb',
            default => self::getKey($value),
        };
    }
}
