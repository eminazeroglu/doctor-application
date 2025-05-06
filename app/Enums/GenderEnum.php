<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class GenderEnum extends Enum
{
    const Male = 'male';
    const Female = 'female';

    public static function getDescription($value): string
    {
        return match ($value) {
            self::Male => t('enums.man'),
            self::Female => t('enums.woman'),
            default => self::getKey($value),
        };
    }
}
