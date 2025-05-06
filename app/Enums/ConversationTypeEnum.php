<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class ConversationTypeEnum extends Enum
{
    const LISTING = 'listing';     // Elanla bağlı söhbətlər
    const PRIVATE = 'private';     // Şəxsi mesajlaşmalar
    const SUPPORT = 'support';     // Texniki dəstək müraciətləri
    const SYSTEM = 'system';       // Sistem bildirişləri

    public static function getDescription($value): string
    {
        return match ($value) {
            self::LISTING => 'Elan mesajlaşması',
            self::PRIVATE => 'Şəxsi mesajlaşma',
            self::SUPPORT => 'Texniki dəstək',
            self::SYSTEM => 'Sistem bildirişi',
            default => parent::getDescription($value),
        };
    }
}
