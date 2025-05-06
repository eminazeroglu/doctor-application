<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class WidgetTypeEnum extends Enum
{
    const TEXT = 'text';
    const ICON_BOX = 'icon_box';
    const IMAGE = 'image';

    /**
     * @param string|array|null $keys
     * @return string[]
     */
    public static function getValues(string|array|null $keys = null): array
    {
        return [
            self::TEXT,
            self::ICON_BOX,
            self::IMAGE
        ];
    }

    /**
     * @param mixed $value
     * @return string
     */
    public static function getDescription(mixed $value): string
    {
        return match ($value) {
            self::TEXT => 'Text',
            self::ICON_BOX => 'Icon Box',
            self::IMAGE => 'Image',
            default => $value
        };
    }
}
