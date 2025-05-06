<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class ActivityLogStatusEnum extends Enum
{
    const SUCCESS = 'success';
    const ERROR = 'error';
    const PENDING = 'pending';
    const CANCELLED = 'cancelled';

    public static function getDescription($value): string
    {
        return match ($value) {
            self::SUCCESS => t('enums.activity_log.status.success'),
            self::ERROR => t('enums.activity_log.status.error'),
            self::PENDING => t('enums.activity_log.status.pending'),
            self::CANCELLED => t('enums.activity_log.status.cancelled'),
            default => self::getKey($value),
        };
    }
}
