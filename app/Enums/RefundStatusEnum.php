<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * Geri ödəmə statusları.
 * Bu enum sistemdə geri ödəmələrin mümkün statuslarını müəyyən edir.
 */
final class RefundStatusEnum extends Enum
{
    /**
     * Gözləmədə olan geri ödəmə.
     * Bu status geri ödəmə tələbi yaradıldıqda və hələ işlənmədikdə istifadə olunur.
     */
    const Pending = 'pending';

    /**
     * Tamamlanmış geri ödəmə.
     * Bu status geri ödəmə uğurla tamamlandıqda istifadə olunur.
     */
    const Completed = 'completed';

    /**
     * Uğursuz geri ödəmə.
     * Bu status geri ödəmə uğursuz olduqda istifadə olunur.
     */
    const Failed = 'failed';

    /**
     * Status üçün insan oxuya biləcəyi təsviri qaytarır.
     *
     * @param mixed $value Status dəyəri
     * @return string Status təsviri
     */
    public static function getDescription($value): string
    {
        return match ($value) {
            self::Pending => 'Gözləmədə',
            self::Completed => 'Tamamlanmış',
            self::Failed => 'Uğursuz',
            default => self::getKey($value),
        };
    }

    /**
     * Status üçün rəng kodu qaytarır.
     *
     * @param mixed $value Status dəyəri
     * @return string Rəng kodu
     */
    public static function getColor($value): string
    {
        return match ($value) {
            self::Pending => 'warning',
            self::Completed => 'success',
            self::Failed => 'danger',
            default => 'primary',
        };
    }

    /**
     * Status üçün simvol/ikon kodu qaytarır.
     *
     * @param mixed $value Status dəyəri
     * @return string Simvol/ikon kodu
     */
    public static function getIcon($value): string
    {
        return match ($value) {
            self::Pending => 'clock',
            self::Completed => 'check-circle',
            self::Failed => 'x-circle',
            default => 'circle',
        };
    }
}
