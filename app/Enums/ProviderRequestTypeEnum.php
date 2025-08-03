<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * Təchizatçı sorğu növləri.
 * Bu enum sistemdə ödəniş təchizatçılarına edilən sorğuların mümkün növlərini müəyyən edir.
 */
final class ProviderRequestTypeEnum extends Enum
{
    /**
     * Ödəniş sorğusu.
     * Bu növ ödəniş edilərkən istifadə olunur.
     */
    const Payment = 'payment';

    /**
     * Geri ödəmə sorğusu.
     * Bu növ ödəniş geri qaytarılarkən istifadə olunur.
     */
    const Refund = 'refund';

    /**
     * Status yoxlaması sorğusu.
     * Bu növ ödəniş statusunu yoxlamaq üçün istifadə olunur.
     */
    const StatusCheck = 'status_check';

    /**
     * Avtorizasiya sorğusu.
     * Bu növ ödəniş avtorizasiyası üçün istifadə olunur.
     */
    const Authorization = 'authorization';

    /**
     * Tutulma sorğusu.
     * Bu növ avtorizasiya edilmiş ödənişi tutmaq üçün istifadə olunur.
     */
    const Capture = 'capture';

    /**
     * Ləğv etmə sorğusu.
     * Bu növ avtorizasiya edilmiş ödənişi ləğv etmək üçün istifadə olunur.
     */
    const Void = 'void';

    /**
     * Sorğu növü üçün insan oxuya biləcəyi təsviri qaytarır.
     *
     * @param mixed $value Növ dəyəri
     * @return string Növ təsviri
     */
    public static function getDescription($value): string
    {
        return match ($value) {
            self::Payment => 'Ödəniş',
            self::Refund => 'Geri ödəmə',
            self::StatusCheck => 'Status yoxlaması',
            self::Authorization => 'Avtorizasiya',
            self::Capture => 'Tutulma',
            self::Void => 'Ləğv etmə',
            default => self::getKey($value),
        };
    }

    /**
     * Sorğu növü üçün simvol/ikon kodu qaytarır.
     *
     * @param mixed $value Növ dəyəri
     * @return string Simvol/ikon kodu
     */
    public static function getIcon($value): string
    {
        return match ($value) {
            self::Payment => 'credit-card',
            self::Refund => 'corner-up-left',
            self::StatusCheck => 'search',
            self::Authorization => 'lock',
            self::Capture => 'download',
            self::Void => 'x',
            default => 'activity',
        };
    }
}
