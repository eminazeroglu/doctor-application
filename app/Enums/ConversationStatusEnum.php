<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class ConversationStatusEnum extends Enum
{
    const ACTIVE = 'active';       // Aktiv söhbət
    const BLOCKED = 'blocked';     // Bloklanmış söhbət
    const ARCHIVED = 'archived';   // Arxivləşdirilmiş söhbət
    const DELETED = 'deleted';     // Silinmiş söhbət

    public static function getDescription($value): string
    {
        return match ($value) {
            self::ACTIVE => 'Aktiv',
            self::BLOCKED => 'Bloklanıb',
            self::ARCHIVED => 'Arxivdə',
            self::DELETED => 'Silinib',
            default => parent::getDescription($value),
        };
    }
}
