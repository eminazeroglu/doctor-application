<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * Əməliyyat növləri.
 * Bu enum sistemdə ödəniş əməliyyatlarının mümkün növlərini müəyyən edir.
 */
final class TransactionTypeEnum extends Enum
{
    /**
     * Ödəniş əməliyyatı.
     * Bu növ ödəniş edilərkən istifadə olunur.
     */
    const Payment = 'payment';

    /**
     * Geri ödəmə əməliyyatı.
     * Bu növ ödəniş geri qaytarılarkən istifadə olunur.
     */
    const Refund = 'refund';

    /**
     * Ödəniş geri alması əməliyyatı.
     * Bu növ ödəniş geri alınarkən istifadə olunur.
     */
    const Chargeback = 'chargeback';

    /**
     * Komissiya əməliyyatı.
     * Bu növ ödəniş zamanı komissiya tutularkən istifadə olunur.
     */
    const Fee = 'fee';

    /**
     * Ödəmə əməliyyatı.
     * Bu növ pul köçürmələri edilərkən istifadə olunur.
     */
    const Payout = 'payout';

    /**
     * Əməliyyat növü üçün insan oxuya biləcəyi təsviri qaytarır.
     *
     * @param mixed $value Növ dəyəri
     * @return string Növ təsviri
     */
    public static function getDescription($value): string
    {
        return match ($value) {
            self::Payment => 'Ödəniş',
            self::Refund => 'Geri ödəmə',
            self::Chargeback => 'Ödəniş geri alması',
            self::Fee => 'Komissiya',
            self::Payout => 'Ödəmə',
            default => self::getKey($value),
        };
    }

    /**
     * Əməliyyat növü üçün simvol/ikon kodu qaytarır.
     *
     * @param mixed $value Növ dəyəri
     * @return string Simvol/ikon kodu
     */
    public static function getIcon($value): string
    {
        return match ($value) {
            self::Payment => 'credit-card',
            self::Refund => 'corner-up-left',
            self::Chargeback => 'alert-triangle',
            self::Fee => 'percent',
            self::Payout => 'send',
            default => 'activity',
        };
    }
}
