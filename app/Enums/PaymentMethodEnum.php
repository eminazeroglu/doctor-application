<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * Ödəniş metodları.
 * Bu enum sistemdə istifadə olunan ödəniş metodlarını müəyyən edir.
 */
final class PaymentMethodEnum extends Enum
{
    /**
     * Bank kartı ilə ödəniş.
     */
    const Card = 'card';

    /**
     * Nağd ödəniş.
     */
    const Cash = 'cash';

    /**
     * Bank köçürməsi ilə ödəniş.
     */
    const BankTransfer = 'bank_transfer';

    /**
     * Elektron pul kisəsi ilə ödəniş.
     */
    const EWallet = 'e_wallet';

    /**
     * Ödəniş metodu üçün insan oxuya biləcəyi təsviri qaytarır.
     *
     * @param mixed $value Ödəniş metodu dəyəri
     * @return string Ödəniş metodu təsviri
     */
    public static function getDescription($value): string
    {
        return match ($value) {
            self::Card => 'Bank kartı',
            self::Cash => 'Nağd',
            self::BankTransfer => 'Bank köçürməsi',
            self::EWallet => 'Elektron pul kisəsi',
            default => self::getKey($value),
        };
    }

    /**
     * Ödəniş metodu üçün ikon adını qaytarır.
     *
     * @param mixed $value Ödəniş metodu dəyəri
     * @return string İkon adı
     */
    public static function getIcon($value): string
    {
        return match ($value) {
            self::Card => 'credit-card',
            self::Cash => 'cash',
            self::BankTransfer => 'bank',
            self::EWallet => 'wallet',
            default => 'money',
        };
    }

    /**
     * Ödəniş metodu üçün komissiya faizini qaytarır.
     *
     * @param mixed $value Ödəniş metodu dəyəri
     * @return float Komissiya faizi
     */
    public static function getFee($value): float
    {
        return match ($value) {
            self::Card => 1.5,
            self::Cash => 0,
            self::BankTransfer => 0.5,
            self::EWallet => 1.0,
            default => 0,
        };
    }
}
