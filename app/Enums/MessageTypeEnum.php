<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class MessageTypeEnum extends Enum
{
    // Mətn tipli sadə mesaj
    const TEXT = 'text';

    // Şəkil mesajı
    const IMAGE = 'image';

    // Video mesajı
    const VIDEO = 'video';

    // Fayl mesajı (sənəd, PDF və s.)
    const FILE = 'file';

    // Sistem mesajı (avtomatik göndərilən bildirişlər)
    const SYSTEM = 'system';

    // Səs yazısı
    const VOICE = 'voice';

    // Elan paylaşımı
    const LISTING = 'listing';

    // Lokasiya paylaşımı
    const LOCATION = 'location';

    // Kontakt paylaşımı
    const CONTACT = 'contact';

    public static function getDescription($value): string
    {
        return match ($value) {
            self::TEXT => 'Mətn mesajı',
            self::IMAGE => 'Şəkil',
            self::VIDEO => 'Video',
            self::FILE => 'Fayl',
            self::SYSTEM => 'Sistem bildirişi',
            self::VOICE => 'Səs yazısı',
            self::LISTING => 'Elan paylaşımı',
            self::LOCATION => 'Məkan',
            self::CONTACT => 'Kontakt',
            default => parent::getDescription($value),
        };
    }
}
