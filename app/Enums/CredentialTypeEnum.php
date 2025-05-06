<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class CredentialTypeEnum extends Enum
{
    const Email = 'email';
    const Phone = 'phone';
    const IP = 'ip';

    public static function getDescription($value): string
    {
        return match ($value) {
            self::Email => t('enums.credential_types.email'),
            self::Phone => t('enums.credential_types.phone'),
            self::IP => t('enums.credential_types.ip'),
            default => self::getKey($value),
        };
    }
}
