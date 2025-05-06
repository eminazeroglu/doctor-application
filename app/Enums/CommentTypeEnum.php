<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class CommentTypeEnum extends Enum
{
    const Company = 'App\Models\Company';
    const Listing = 'App\Models\Listing';
    const User = 'App\Models\User';

    public static function getDescription($value): string
    {
        return match ($value) {
            self::Company => t('enums.comment_type.company'),
            self::Listing => t('enums.comment_type.listing'),
            self::User => t('enums.comment_type.user'),
            default => self::getKey($value),
        };
    }

    /**
     */
    public static function getClassPath($value): string
    {
        return match ($value) {
            'company' => self::Company,
            'listing' => self::Listing,
            'user' => self::User,
            default => false,
        };
    }
}
