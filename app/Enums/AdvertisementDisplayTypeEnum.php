<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class AdvertisementDisplayTypeEnum extends Enum
{
    const HOME_ONLY = 'home_only';
    const ALL_PAGES = 'all_pages';
    const SELECTED_CATEGORIES = 'selected_categories';

    public static function getDescription($value): string
    {
        return match ($value) {
            self::HOME_ONLY => t('enums.advertisement_display_types.home_only'),
            self::ALL_PAGES => t('enums.advertisement_display_types.all_pages'),
            self::SELECTED_CATEGORIES => t('enums.advertisement_display_types.selected_categories'),
            default => self::getKey($value),
        };
    }
}
