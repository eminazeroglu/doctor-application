<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * Geri ödəmə səbəbləri.
 * Bu enum sistemdə geri ödəmələrin mümkün səbəblərini müəyyən edir.
 */
final class RefundReasonEnum extends Enum
{
    /**
     * Müştəri tələbi.
     * Bu səbəb müştəri ödənişi geri qaytarmağı tələb etdikdə istifadə olunur.
     */
    const CustomerRequest = 'customer_request';

    /**
     * Təkrar ödəniş.
     * Bu səbəb eyni ödəniş birdən çox dəfə edildikdə istifadə olunur.
     */
    const DuplicatePayment = 'duplicate_payment';

    /**
     * Saxta ödəniş.
     * Bu səbəb ödəniş icazəsiz və ya fırıldaqçılıq yolu ilə edildikdə istifadə olunur.
     */
    const FraudulentCharge = 'fraudulent_charge';

    /**
     * Sifariş ləğvi.
     * Bu səbəb sifariş ləğv edildikdə istifadə olunur.
     */
    const OrderCancellation = 'order_cancellation';

    /**
     * Xidmət göstərilməyib.
     * Bu səbəb ödəniş edilmiş xidmət göstərilmədikdə istifadə olunur.
     */
    const ServiceNotProvided = 'service_not_provided';

    /**
     * Digər.
     * Bu səbəb digər səbəblər üçün istifadə olunur.
     */
    const Other = 'other';

    /**
     * Səbəb üçün insan oxuya biləcəyi təsviri qaytarır.
     *
     * @param mixed $value Səbəb dəyəri
     * @return string Səbəb təsviri
     */
    public static function getDescription($value): string
    {
        return match ($value) {
            self::CustomerRequest => 'Müştəri tələbi',
            self::DuplicatePayment => 'Təkrar ödəniş',
            self::FraudulentCharge => 'Saxta ödəniş',
            self::OrderCancellation => 'Sifariş ləğvi',
            self::ServiceNotProvided => 'Xidmət göstərilməyib',
            self::Other => 'Digər',
            default => self::getKey($value),
        };
    }

    /**
     * Səbəb üçün simvol/ikon kodu qaytarır.
     *
     * @param mixed $value Səbəb dəyəri
     * @return string Simvol/ikon kodu
     */
    public static function getIcon($value): string
    {
        return match ($value) {
            self::CustomerRequest => 'user',
            self::DuplicatePayment => 'copy',
            self::FraudulentCharge => 'alert-triangle',
            self::OrderCancellation => 'x-circle',
            self::ServiceNotProvided => 'slash',
            self::Other => 'help-circle',
            default => 'circle',
        };
    }
}
