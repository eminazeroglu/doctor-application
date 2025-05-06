<?php

namespace App\Enums;

class ComplaintTypeEnum
{
    const Company = \App\Models\Company::class;
    const Listing = \App\Models\Listing::class;
    const User = \App\Models\User::class;

    public static function getValues(): array
    {
        return [
            self::User,
            self::Company,
            self::Listing
        ];
    }

    public static function getDescription(string $value): string
    {
        return match ($value) {
            self::User => t('enums.complaint_type.user'),
            self::Company => t('enums.complaint_type.company'),
            self::Listing => t('enums.complaint_type.listing'),
            default => ''
        };
    }

    public static function getModel(string $value): string
    {
        return match ($value) {
            self::User => \App\Models\User::class,
            self::Company => \App\Models\Company::class,
            self::Listing => \App\Models\Listing::class,
            default => ''
        };
    }
}
