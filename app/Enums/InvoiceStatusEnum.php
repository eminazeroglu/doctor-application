<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * Faktura statusları.
 * Bu enum sistemdə fakturaların mümkün statuslarını müəyyən edir.
 */
final class InvoiceStatusEnum extends Enum
{
    /**
     * Qaralama faktura.
     * Bu status faktura yaradıldıqda və hələ göndərilmədikdə istifadə olunur.
     */
    const Draft = 'draft';

    /**
     * Göndərilmiş faktura.
     * Bu status faktura müştəriyə göndərildikdə istifadə olunur.
     */
    const Sent = 'sent';

    /**
     * Ödənilmiş faktura.
     * Bu status faktura tam ödənildikdə istifadə olunur.
     */
    const Paid = 'paid';

    /**
     * Vaxtı keçmiş faktura.
     * Bu status fakturanın ödəniş müddəti bitdikdə istifadə olunur.
     */
    const Overdue = 'overdue';

    /**
     * Ləğv edilmiş faktura.
     * Bu status faktura ləğv edildikdə istifadə olunur.
     */
    const Cancelled = 'cancelled';

    /**
     * Status üçün insan oxuya biləcəyi təsviri qaytarır.
     *
     * @param mixed $value Status dəyəri
     * @return string Status təsviri
     */
    public static function getDescription($value): string
    {
        return match ($value) {
            self::Draft => 'Qaralama',
            self::Sent => 'Göndərilmiş',
            self::Paid => 'Ödənilmiş',
            self::Overdue => 'Vaxtı keçmiş',
            self::Cancelled => 'Ləğv edilmiş',
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
            self::Draft => 'secondary',
            self::Sent => 'primary',
            self::Paid => 'success',
            self::Overdue => 'danger',
            self::Cancelled => 'warning',
            default => 'info',
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
            self::Draft => 'edit',
            self::Sent => 'send',
            self::Paid => 'check-circle',
            self::Overdue => 'alert-circle',
            self::Cancelled => 'x-circle',
            default => 'file-text',
        };
    }
}
