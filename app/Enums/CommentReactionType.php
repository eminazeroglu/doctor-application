<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class CommentReactionType extends Enum
{
    const Like = 'like';
    const Dislike = 'dislike';
    const Heart = 'heart';
    const Laugh = 'laugh';
    const Wow = 'wow';
    const Sad = 'sad';
    const Angry = 'angry';
    const Support = 'support';

    public static function getDescription($value): string
    {
        return match ($value) {
            self::Like => t('enums.comment_reaction_type.like'),
            self::Dislike => t('enums.comment_reaction_type.dislike'),
            self::Heart => t('enums.comment_reaction_type.heart'),
            self::Laugh => t('enums.comment_reaction_type.laugh'),
            self::Wow => t('enums.comment_reaction_type.wow'),
            self::Sad => t('enums.comment_reaction_type.sad'),
            self::Angry => t('enums.comment_reaction_type.angry'),
            self::Support => t('enums.comment_reaction_type.support'),
            default => self::getKey($value),
        };
    }
}
