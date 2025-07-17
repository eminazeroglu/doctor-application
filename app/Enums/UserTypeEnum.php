<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class UserTypeEnum extends Enum
{
    const User = 'user';
    const Doctor = 'doctor';

    public static function getDescription($value): string
    {
        return match ($value) {
            self::User => 'İstifadəçi',
            self::Doctor => 'Həkim',
            default => self::getKey($value),
        };
    }
}
