<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class AdvertisementPositionEnum extends Enum
{
    const WEB_TOP = 'web_top';                         // Üst (Veb)
    const MOBILE_TOP = 'mobile_top';                   // Üst (Mobil)
    const LISTING_TOP = 'listing_top';                 // Elan (Üst)
    const LISTING_BOTTOM = 'listing_bottom';           // Elan (Alt)
    const LISTING_RIGHT = 'listing_right';             // Elan (Sağ)
    const LISTING_MOBILE_TOP = 'listing_mobile_top';   // Elan (Mobil üst)
    const LISTING_MOBILE_BOTTOM = 'listing_mobile_bottom'; // Elan (Mobil alt)
    const LISTING_MOBILE_RIGHT = 'listing_mobile_right';   // Elan (Mobil sağ)
    const SIMILAR_AFTER_MOBILE = 'similar_after_mobile';   // Bənzər elanlardan sonra (Mobil)
    const SIMILAR_AFTER_WEB = 'similar_after_web';         // Bənzər elanlardan sonra (Web)
    const LATEST_LISTINGS = 'latest_listings';             // Son elanlarda

    public static function getDescription($value): string
    {
        return match ($value) {
            self::WEB_TOP => t('enums.advertisement_positions.web_top'),
            self::MOBILE_TOP => t('enums.advertisement_positions.mobile_top'),
            self::LISTING_TOP => t('enums.advertisement_positions.listing_top'),
            self::LISTING_BOTTOM => t('enums.advertisement_positions.listing_bottom'),
            self::LISTING_RIGHT => t('enums.advertisement_positions.listing_right'),
            self::LISTING_MOBILE_TOP => t('enums.advertisement_positions.listing_mobile_top'),
            self::LISTING_MOBILE_BOTTOM => t('enums.advertisement_positions.listing_mobile_bottom'),
            self::LISTING_MOBILE_RIGHT => t('enums.advertisement_positions.listing_mobile_right'),
            self::SIMILAR_AFTER_MOBILE => t('enums.advertisement_positions.similar_after_mobile'),
            self::SIMILAR_AFTER_WEB => t('enums.advertisement_positions.similar_after_web'),
            self::LATEST_LISTINGS => t('enums.advertisement_positions.latest_listings'),
            default => self::getKey($value),
        };
    }
}
