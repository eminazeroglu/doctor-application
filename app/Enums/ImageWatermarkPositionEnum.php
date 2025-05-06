<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class ImageWatermarkPositionEnum extends Enum
{
    const TopLeft = 'top-left';
    const Top = 'top';
    const TopRight = 'top-right';
    const Left = 'left';
    const Center = 'center';
    const Right = 'right';
    const BottomLeft = 'bottom-left';
    const Bottom = 'bottom';
    const BottomRight = 'bottom-right';

    public static function getDescription($value): string
    {
        return match ($value) {
            self::TopLeft => t('enums.image_watermark_position.top_left'),
            self::Top => t('enums.image_watermark_position.top'),
            self::TopRight => t('enums.image_watermark_position.top_right'),
            self::Left => t('enums.image_watermark_position.left'),
            self::Center => t('enums.image_watermark_position.center'),
            self::Right => t('enums.image_watermark_position.right'),
            self::BottomLeft => t('enums.image_watermark_position.bottom_left'),
            self::Bottom => t('enums.image_watermark_position.bottom'),
            self::BottomRight => t('enums.image_watermark_position.bottom_right'),
            default => self::getKey($value),
        };
    }
}
