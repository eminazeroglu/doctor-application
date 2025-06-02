<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class AttributePositionEnum extends Enum
{
    const OnlyMoreSearch = 'only-more-search';
    const OnlyListing = 'only-listing';
    const EveryWhere = 'every-where';

    public static function getDescription($value): string
    {
        return match ($value) {
            self::OnlyMoreSearch => t('enums.attributePosition.only-more-search'),
            self::OnlyListing => t('enums.attributePosition.only-listing'),
            self::EveryWhere => t('enums.attributePosition.every-where'),
            default => self::getKey($value),
        };
    }
}
