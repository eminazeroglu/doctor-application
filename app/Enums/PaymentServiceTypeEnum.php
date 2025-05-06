<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class PaymentServiceTypeEnum extends Enum
{
    const Listing = 'listing';
    const Company = 'company';

    public static function getDescription($value): string
    {
        return match ($value) {
            self::Listing => 'Elan üçün',
            self::Company => 'Şirkət üçün',
            default => self::getKey($value),
        };
    }
}
