<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class PaymentMethodEnum extends Enum
{
    const Card = 'card';
    const Balance = 'balance';

    public static function getDescription($value): string
    {
        return match ($value) {
            self::Card => t('enums.payment_method.card'),
            self::Balance => t('enums.payment_method.balance'),
            default => self::getKey($value),
        };
    }
}
