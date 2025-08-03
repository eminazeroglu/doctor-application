<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class NotificationTypeEnum extends Enum
{
    const AppointmentCreated = 'appointment_created';
    const AppointmentConfirmed = 'appointment_confirmed';
    const AppointmentCancelled = 'appointment_created';
    const AppointmentReminder = 'appointment_reminder';
    const ReviewReceived = 'review_received';
    const ReviewResponse = 'review_response';
    const MessageReceived = 'message_received';
    const System = 'system';

    public static function getDescription($value): string
    {
        return match ($value) {
            self::AppointmentCreated => 'Yeni randevu',
            self::AppointmentConfirmed => 'Randevu təsdiqləndi',
            self::AppointmentCancelled => 'Randevu ləğv edildi',
            self::AppointmentReminder => 'Randevu xatırlatması',
            self::ReviewReceived => 'Yeni rəy',
            self::ReviewResponse => 'Rəyə cavab',
            self::MessageReceived => 'Yeni mesaj',
            self::System => 'Sistem bildirişi',
            default => self::getKey($value),
        };
    }
}
