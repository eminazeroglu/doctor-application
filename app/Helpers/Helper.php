<?php

namespace App\Helpers;

use Exception;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

class Helper
{
    /**
     * Get the current language.
     *
     * @return string
     */
    public static function language(): string
    {
        return request()->header('Content-Language')
            ?? session('lang')
            ?? App::getLocale();
    }

    /**
     * Generate a short URL.
     *
     * @param string $modelClass
     * @param string $field
     * @return string
     * @throws Exception
     */
    public static function shortUrl(string $modelClass, string $field = 'short_url'): string
    {
        do {
            $short = Str::random(4) . random_int(1000, 9999);
        } while ($modelClass::where($field, $short)->exists());

        return $short;
    }

    /**
     * Create a unique slug.
     *
     * @param string $modelClass
     * @param string $text
     * @param string $field
     * @return string
     */
    public static function createSlug(string $modelClass, string $text, string $field = 'url'): string
    {
        $slug = Str::slug($text);
        $count = $modelClass::where($field, 'LIKE', $slug . '%')->count();
        return $count ? "{$slug}-{$count}" : $slug;
    }

    /**
     * Get content from a URL.
     *
     * @param string $url
     * @param array|null $postData
     * @return string
     */
    public static function getUrlContent(string $url, ?array $postData = null): string
    {
        $response = $postData
            ? Http::post($url, $postData)
            : Http::get($url);

        return $response->body();
    }

    /**
     * Encrypt a string.
     *
     * @param string $text
     * @return string
     */
    public static function encrypt(string $text): string
    {
        return Crypt::encryptString($text);
    }

    /**
     * Decrypt a string.
     *
     * @param string $text
     * @return string
     */
    public static function decrypt(string $text): string
    {
        try {
            return Crypt::decryptString($text);
        } catch (DecryptException $e) {
            return '';
        }
    }

    /**
     * Get human-readable file size.
     *
     * @param string $path
     * @return string
     */
    public static function fileSize(string $path): string
    {
        $bytes = Storage::size($path);
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Mask a string, showing specified number of characters from start and end.
     *
     * @param string $text The text to be masked
     * @param int $visibleStart Number of characters to leave visible from the start
     * @param int $visibleEnd Number of characters to leave visible from the end
     * @return string
     */
    public static function maskString(string $text, int $visibleStart = 1, int $visibleEnd = 1): string
    {
        if ($visibleStart < 0 || $visibleEnd < 0) {
            throw new InvalidArgumentException("Number of visible characters must be non-negative.");
        }

        $length = mb_strlen($text);

        if ($visibleStart + $visibleEnd >= $length) {
            return $text;
        }

        $startPart = mb_substr($text, 0, $visibleStart);
        $endPart = mb_substr($text, -$visibleEnd);
        $maskedLength = $length - $visibleStart - $visibleEnd;
        $maskedPart = str_repeat('*', $maskedLength);

        return $startPart . $maskedPart . $endPart;
    }

    /**
     * Generate a unique identifier.
     *
     * @param string $modelClass
     * @param string $field
     * @param string $type
     * @param int $length
     * @return string
     * @throws InvalidArgumentException|Exception
     */
    public static function generateUniqueIdentifier(
        string $modelClass,
        string $field = 'code',
        string $type = 'number',
        int $length = 6,
        bool $uppercase = false
    ): string {
        // Mümkün tip seçimlərini genişləndiririk
        $allowedTypes = ['number', 'string', 'mixed'];

        if (!in_array($type, $allowedTypes)) {
            throw new InvalidArgumentException("Invalid type. Must be 'number', 'string' or 'mixed'.");
        }

        do {
            // Tip seçiminə görə müxtəlif generasiya metodları
            $identifier = match($type) {
                'number' => str_pad(
                    (string)random_int(0, 10 ** $length - 1),
                    $length,
                    '0',
                    STR_PAD_LEFT
                ),
                'string' => Str::random($length),
                'mixed' => static::generateMixedCode($length)
            };

        } while ($modelClass::where($field, $identifier)->exists());

        return $uppercase ? str($identifier)->upper() : $identifier;
    }

    /**
     * Həm hərf həm rəqəm qarışıq unikal kod yaradır
     * Nümunə çıxışlar: "A2B5C9", "X1Y2Z3", "L7M8N9"
     */
    private static function generateMixedCode(int $length): string
    {
        // Bütün mümkün simvollar
        $numbers = '0123456789';
        $upperLetters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowerLetters = 'abcdefghijklmnopqrstuvwxyz';

        // Bütün simvolları birləşdiririk
        $allCharacters = $numbers . $upperLetters . $lowerLetters;

        $code = '';

        // Ən azı bir rəqəm olmasını təmin edirik
        $code .= $numbers[random_int(0, strlen($numbers) - 1)];

        // Ən azı bir böyük hərf olmasını təmin edirik
        $code .= $upperLetters[random_int(0, strlen($upperLetters) - 1)];

        // Ən azı bir kiçik hərf olmasını təmin edirik
        $code .= $lowerLetters[random_int(0, strlen($lowerLetters) - 1)];

        // Qalan simvolları random seçirik
        for ($i = 3; $i < $length; $i++) {
            $code .= $allCharacters[random_int(0, strlen($allCharacters) - 1)];
        }

        // Kodu qarışdırırıq ki, rəqəm, böyük və kiçik hərflər random düzülsün
        return str_shuffle($code);
    }

    /**
     * Format price with currency symbol.
     *
     * @param float $price
     * @param string $currency
     * @return string
     */
    public static function formatPrice(float $price, string $currency = '₼'): string
    {
        return number_format($price, 2) . ' ' . $currency;
    }

    /**
     * Generate a random number string of specified length.
     *
     * @param int $length The desired length of the generated number string
     * @return string
     * @throws Exception
     */
    public static function generateNumber(int $length = 6): string
    {
        if ($length <= 0) {
            throw new InvalidArgumentException("Length must be a positive integer.");
        }

        $min = pow(10, $length - 1);
        $max = pow(10, $length) - 1;

        return (string) random_int($min, $max);
    }

    /**
     * Enum dəyərlərini filter məlumatlarına çevirir.
     *
     * @param string $enumClass Enum sinfinin tam adı (məsələn, 'App\Enums\ComplaintStatusEnum')
     * @return Collection Filter formatında enum dəyərləri (id və name)
     */
    public static function enumToFilter(string $enumClass): Collection
    {
        if (!class_exists($enumClass)) {
            throw new \InvalidArgumentException("Enum sinfi '{$enumClass}' mövcud deyil.");
        }

        // Enum sinfinin getValues və getDescription metodlarını yoxlayırıq
        if (!method_exists($enumClass, 'getValues') || !method_exists($enumClass, 'getDescription')) {
            throw new \InvalidArgumentException("Enum sinfi '{$enumClass}'-da 'getValues' və ya 'getDescription' metodları yoxdur.");
        }

        return collect($enumClass::getValues())->map(fn($value) => [
            'id' => $value,
            'name' => $enumClass::getDescription($value),
        ]);
    }

    public static function getLocationFromIp(string $ipAddress): array|string
    {
        if (in_array($ipAddress, ['127.0.0.1', 'localhost', '::1']) ||
            preg_match('/^(10\.|172\.(1[6-9]|2[0-9]|3[0-1])\.|192\.168\.)/', $ipAddress)) {
            return [
                'location' => 'Local Environment',
            ];
        }

        try {
            $response = Http::get("http://ip-api.com/json/{$ipAddress}");

            if ($response->successful()) {
                $data = $response->json();

                if ($data['status'] === 'success') {
                    return [
                        'location' => $data['city'] . ', ' . $data['country'],
                        'meta_data' => $data
                    ];
                }
            }

            return [
                'location' => 'Unknown Location',
            ];
        } catch (\Exception $e) {
            return [
                'location' => 'Location Service Error',
            ];
        }
    }
}
