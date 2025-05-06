<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class UserStatusEnum extends Enum
{
    const Active = 'active';
    const Inactive = 'inactive';
    const PendingMail = 'pending_mail';
    const PendingProfile = 'pending_profile';
    const Block = 'block';

    public static function getDescription($value): string
    {
        return match ($value) {
            self::Active => t('enums.user_status.active'),
            self::Inactive => t('enums.user_status.inactive'),
            self::PendingMail => t('enums.user_status.pending'),
            self::PendingProfile => t('enums.user_status.profile'),
            self::Block => t('enums.user_status.block'),
            default => self::getKey($value),
        };
    }
}
