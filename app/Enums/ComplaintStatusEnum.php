<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

class ComplaintStatusEnum extends Enum
{
    public const Pending = 'pending';       // Yeni şikayət, hələ baxılmayıb
    public const InProgress = 'processing'; // Şikayətə baxılır
    public const Resolved = 'resolved';     // Şikayət həll edilib
    public const Rejected = 'rejected';     // Şikayət rədd edilib
    public const Closed = 'closed';         // Şikayət bağlanıb

    public static function getValues(string|array|null $keys = null): array
    {
        return [
            self::Pending,
            self::InProgress,
            self::Resolved,
            self::Rejected,
            self::Closed
        ];
    }

    public static function getDescription(mixed $value): string
    {
        return match ($value) {
            self::Pending => t('enums.complaint_status.pending'),
            self::InProgress => t('enums.complaint_status.processing'),
            self::Resolved => t('enums.complaint_status.resolved'),
            self::Rejected => t('enums.complaint_status.rejected'),
            self::Closed => t('enums.complaint_status.closed'),
            default => ''
        };
    }
}
