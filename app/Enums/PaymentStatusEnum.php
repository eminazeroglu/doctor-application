<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * Ödəniş statusları.
 * Bu enum sistemdə ödənişlərin mümkün statuslarını müəyyən edir.
 */
final class PaymentStatusEnum extends Enum
{
    /**
     * Gözləmədə olan ödəniş.
     * Bu status ödəniş yaradıldıqda və hələ tamamlanmadıqda istifadə olunur.
     */
    const Pending = 'pending';

    /**
     * Tamamlanmış ödəniş.
     * Bu status ödəniş uğurla tamamlandıqda istifadə olunur.
     */
    const Completed = 'completed';

    /**
     * Uğursuz ödəniş.
     * Bu status ödəniş uğursuz olduqda istifadə olunur.
     */
    const Failed = 'failed';

    /**
     * Geri ödənilmiş ödəniş.
     * Bu status ödəniş tam geri ödənildikdə istifadə olunur.
     */
    const Refunded = 'refunded';

    /**
     * Qismən geri ödənilmiş ödəniş.
     * Bu status ödəniş qismən geri ödənildikdə istifadə olunur.
     */
    const PartiallyRefunded = 'partially_refunded';

    /**
     * Ləğv edilmiş ödəniş.
     * Bu status ödəniş ləğv edildikdə istifadə olunur.
     */
    const Cancelled = 'cancelled';

    /**
     * Vaxtı bitmiş ödəniş.
     * Bu status ödənişin müddəti bitdikdə istifadə olunur.
     */
    const Expired = 'expired';

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
            self::Refunded => 'Geri ödənilmiş',
            self::PartiallyRefunded => 'Qismən geri ödənilmiş',
            self::Cancelled => 'Ləğv edilmiş',
            self::Expired => 'Vaxtı bitmiş',
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
            self::Refunded, self::PartiallyRefunded => 'info',
            self::Cancelled, self::Expired => 'secondary',
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
            self::Refunded => 'refresh-cw',
            self::PartiallyRefunded => 'refresh-ccw',
            self::Cancelled => 'slash',
            self::Expired => 'alert-triangle',
            default => 'circle',
        };
    }
}
