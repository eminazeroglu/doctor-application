<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class PageTypeEnum extends Enum
{
    const STANDARD = 'standard';
    const TERMS = 'terms';
    const POLICY = 'policy';
    const ABOUT = 'about';
    const FAQ = 'faq';
    const HELP = 'help';
    const CUSTOM = 'custom';

    /**
     * @param string|array|null $keys
     * @return string[]
     */
    public static function getValues(string|array|null $keys = null): array
    {
        return [
            self::STANDARD,
            self::TERMS,
            self::POLICY,
            self::ABOUT,
            self::FAQ,
            self::HELP,
            self::CUSTOM
        ];
    }

    /**
     * @param mixed $value
     * @return string
     */
    public static function getDescription(mixed $value): string
    {
        return match ($value) {
            self::STANDARD => 'Standard',
            self::TERMS => 'Terms',
            self::POLICY => 'Policy',
            self::ABOUT => 'About',
            self::FAQ => 'FAQ',
            self::HELP => 'Help',
            self::CUSTOM => 'Custom',
            default => $value
        };
    }
}
