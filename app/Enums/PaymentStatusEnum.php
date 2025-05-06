<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class PaymentStatusEnum extends Enum
{
    const Pending = 'pending';      // Gözləmədə (ödəniş işlənir)
    const Completed = 'completed';  // Tamamlanıb (ödəniş uğurlu)
    const Failed = 'failed';        // Uğursuz (ödəniş alınmadı)
    const Refunded = 'refunded';    // Geri qaytarılıb (pul istifadəçiyə qaytarıldı)

    public static function getDescription($value): string
    {
        return match ($value) {
            self::Pending => t('enums.payment_status.pending'),
            self::Completed => t('enums.payment_status.completed'),
            self::Failed => t('enums.payment_status.failed'),
            self::Refunded => t('enums.payment_status.refunded'),
            default => self::getKey($value),
        };
    }
}
