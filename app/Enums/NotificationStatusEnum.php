<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class NotificationStatusEnum extends Enum
{
    const PENDING = 'pending';       // Gözləmədə
    const PROCESSING = 'processing'; // İşlənir
    const SENT = 'sent';            // Göndərilib
    const FAILED = 'failed';        // Uğursuz
    const CANCELLED = 'cancelled';   // Ləğv edilib

    public static function getDescription($value): string
    {
        return match ($value) {
            self::PENDING => t('enums.notification_status.pending'),
            self::PROCESSING => t('enums.notification_status.processing'),
            self::SENT => t('enums.notification_status.sent'),
            self::FAILED => t('enums.notification_status.failed'),
            self::CANCELLED => t('enums.notification_status.cancelled'),
            default => self::getKey($value),
        };
    }
} 