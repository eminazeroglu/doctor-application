<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * Əməliyyat statusları.
 * Bu enum sistemdə ödəniş əməliyyatlarının mümkün statuslarını müəyyən edir.
 */
final class TransactionStatusEnum extends Enum
{
    /**
     * Gözləmədə olan əməliyyat.
     * Bu status əməliyyat başladıldıqda və hələ tamamlanmadıqda istifadə olunur.
     */
    const Pending = 'pending';

    /**
     * Tamamlanmış əməliyyat.
     * Bu status əməliyyat uğurla tamamlandıqda istifadə olunur.
     */
    const Completed = 'completed';

    /**
     * Uğursuz əməliyyat.
     * Bu status əməliyyat uğursuz olduqda istifadə olunur.
     */
    const Failed = 'failed';

    /**
     * Geri qaytarılmış əməliyyat.
     * Bu status əməliyyat geri qaytarıldıqda istifadə olunur.
     */
    const Reversed = 'reversed';

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
            self::Reversed => 'Geri qaytarılmış',
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
            self::Reversed => 'info',
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
            self::Reversed => 'refresh-cw',
            default => 'circle',
        };
    }
}
