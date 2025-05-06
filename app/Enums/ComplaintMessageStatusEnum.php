<?php

namespace App\Enums;

class ComplaintMessageStatusEnum
{
    public const Pending = 'pending';     // Mesaj göndərildi, amma baxılmayıb
    public const Read = 'read';           // Mesaja baxılıb
    public const Responded = 'responded'; // Mesaja cavab verilib
    public const Hidden = 'hidden';       // Mesaj gizlədilib (moderator tərəfindən)

    public static function getValues(): array
    {
        return [
            self::Pending,
            self::Read,
            self::Responded,
            self::Hidden
        ];
    }

    public static function getDescription(string $value): string
    {
        return match ($value) {
            self::Pending => t('enums.complaint_message_status.pending'),
            self::Read => t('enums.complaint_message_status.read'),
            self::Responded => t('enums.complaint_message_status.responded'),
            self::Hidden => t('enums.complaint_message_status.hidden'),
            default => ''
        };
    }
}
