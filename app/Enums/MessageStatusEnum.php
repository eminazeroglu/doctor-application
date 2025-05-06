<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class MessageStatusEnum extends Enum
{
    // Mesaj göndərilib, amma hələ serverə çatmayıb
    const SENDING = 'sending';

    // Mesaj serverə çatıb
    const SENT = 'sent';

    // Mesaj qarşı tərəfin cihazına çatıb
    const DELIVERED = 'delivered';

    // Qarşı tərəf mesajı oxuyub
    const READ = 'read';

    // Mesajın göndərilməsində xəta baş verib
    const FAILED = 'failed';

    public static function getDescription($value): string
    {
        return match ($value) {
            self::SENDING => 'Göndərilir',
            self::SENT => 'Göndərildi',
            self::DELIVERED => 'Çatdırıldı',
            self::READ => 'Oxundu',
            self::FAILED => 'Xəta baş verdi',
            default => parent::getDescription($value),
        };
    }
}
