<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * Randevu statusları.
 * Bu enum randevunun mümkün statuslarını müəyyən edir.
 */
final class AppointmentStatusEnum extends Enum
{
    /**
     * Gözləmədə olan randevu.
     * Bu status randevu yaradıldıqda və hələ təsdiqlənmədikdə istifadə olunur.
     */
    const Pending = 'pending';

    /**
     * Təsdiqlənmiş randevu.
     * Bu status randevu həkim və ya klinika tərəfindən təsdiqləndikdə istifadə olunur.
     */
    const Confirmed = 'confirmed';

    /**
     * Ləğv edilmiş randevu.
     * Bu status randevu həkim, klinika və ya xəstə tərəfindən ləğv edildikdə istifadə olunur.
     */
    const Cancelled = 'cancelled';

    /**
     * Tamamlanmış randevu.
     * Bu status randevu başa çatdıqda istifadə olunur.
     */
    const Completed = 'completed';

    /**
     * Xəstə gəlməyən randevu.
     * Bu status randevu vaxtında xəstə gəlmədikdə istifadə olunur.
     */
    const NoShow = 'no-show';

    /**
     * Yenidən planlaşdırılmış randevu.
     * Bu status randevunun vaxtı dəyişdirildikdə istifadə olunur.
     */
    const Rescheduled = 'rescheduled';

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
            self::Confirmed => 'Təsdiqləndi',
            self::Cancelled => 'Ləğv edildi',
            self::Completed => 'Tamamlandı',
            self::NoShow => 'Gəlmədi',
            self::Rescheduled => 'Yenidən planlaşdırıldı',
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
            self::Pending => 'warning', // Sarı
            self::Confirmed => 'primary', // Göy
            self::Completed => 'success', // Yaşıl
            self::Cancelled => 'danger', // Qırmızı
            self::NoShow => 'secondary', // Boz
            self::Rescheduled => 'info', // Açıq göy
            default => 'default', // Standart
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
            self::Pending => 'clock', // Saat
            self::Confirmed => 'check-circle', // Təsdiq işarəsi
            self::Completed => 'check-double', // İkiqat təsdiq işarəsi
            self::Cancelled => 'x-circle', // X işarəsi
            self::NoShow => 'user-x', // İstifadəçi-X
            self::Rescheduled => 'calendar', // Təqvim
            default => 'circle', // Dairə
        };
    }

    /**
     * Status aktiv olub-olmadığını yoxlayır.
     * Aktiv statuslar: Pending, Confirmed və Rescheduled.
     *
     * @param mixed $value Status dəyəri
     * @return bool Aktiv olarsa true, əks halda false
     */
    public static function isActive($value): bool
    {
        return in_array($value, [
            self::Pending,
            self::Confirmed,
            self::Rescheduled
        ]);
    }

    /**
     * Status tamamlanmış olub-olmadığını yoxlayır.
     * Tamamlanmış statuslar: Completed, Cancelled və NoShow.
     *
     * @param mixed $value Status dəyəri
     * @return bool Tamamlanmış olarsa true, əks halda false
     */
    public static function isCompleted($value): bool
    {
        return in_array($value, [
            self::Completed,
            self::Cancelled,
            self::NoShow
        ]);
    }
}
