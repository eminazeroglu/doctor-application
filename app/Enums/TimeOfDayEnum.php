<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class TimeOfDayEnum extends Enum
{
    const EARLY_MORNING = 'early_morning';    // 06:00 - 09:00
    const MORNING = 'morning';                // 09:00 - 12:00
    const AFTERNOON = 'afternoon';            // 12:00 - 17:00
    const EVENING = 'evening';                // 17:00 - 21:00

    public static function getDescription($value): string
    {
        return match ($value) {
            self::EARLY_MORNING => 'Günün birinci yarısı',
            self::MORNING => 'Səhər tezdən',
            self::AFTERNOON => 'Günortadan sonra',
            self::EVENING => 'Axşam 7-dən sonra',
            default => 'Naməlum vaxt'
        };
    }

    /**
     * Enum dəyərinə görə saat aralığını qaytarır
     *
     * @param string $value
     * @return array ['start' => 'HH:mm', 'end' => 'HH:mm']
     */
    public static function getTimeRange(string $value): array
    {
        return match ($value) {
            self::EARLY_MORNING => ['start' => '06:00', 'end' => '09:00'],
            self::MORNING => ['start' => '09:00', 'end' => '12:00'],
            self::AFTERNOON => ['start' => '12:00', 'end' => '17:00'],
            self::EVENING => ['start' => '17:00', 'end' => '21:00'],
            default => ['start' => '00:00', 'end' => '23:59']
        };
    }

    /**
     * Müəyyən saatın hansı time range-ə aid olduğunu tapır
     *
     * @param string $time HH:mm formatında
     * @return string|null
     */
    public static function getTimeOfDayByTime(string $time): ?string
    {
        $timeMinutes = self::timeToMinutes($time);

        foreach (self::cases() as $case) {
            $range = self::getTimeRange($case->value);
            $startMinutes = self::timeToMinutes($range['start']);
            $endMinutes = self::timeToMinutes($range['end']);

            if ($timeMinutes >= $startMinutes && $timeMinutes < $endMinutes) {
                return $case->value;
            }
        }

        return null;
    }

    /**
     * HH:mm formatını dəqiqəyə çevirir
     */
    private static function timeToMinutes(string $time): int
    {
        [$hour, $minute] = explode(':', $time);
        return (int)$hour * 60 + (int)$minute;
    }
}
