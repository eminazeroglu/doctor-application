<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class SeoTypeEnum extends Enum
{
   // const Page = 'App\\Models\\Page';
    //const Listing = 'App\\Models\\Listing';
    const Category = 'App\\Models\\Category';

    public static function getDescription($value): string
    {
        return match ($value) {
            //self::Page => t('enums.seo_types.page'),
            //self::Listing => t('enums.seo_types.listing'),
            self::Category => t('enums.seo_types.category'),
            default => self::getKey($value),
        };
    }
}
