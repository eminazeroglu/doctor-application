<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Config;

class AzerbaijanPhoneRule implements ValidationRule
{
    /**
     * Bütün operator məlumatlarını saxlayan array
     */
    protected const OPERATORS = [
        'mobile' => [
            '50' => [
                'name' => 'Azercell',
                'type' => 'mobile',
                'formats' => ['50XXXXXXX'],
                'description' => 'Azercell mobil operatoru'
            ],
            '51' => [
                'name' => 'Azercell',
                'type' => 'mobile',
                'formats' => ['51XXXXXXX'],
                'description' => 'Azercell mobil operatoru'
            ],
            '10' => [
                'name' => 'Azercell',
                'type' => 'mobile',
                'formats' => ['10XXXXXXX'],
                'description' => 'Azercell mobil operatoru'
            ],
            '55' => [
                'name' => 'Bakcell',
                'type' => 'mobile',
                'formats' => ['55XXXXXXX'],
                'description' => 'Bakcell mobil operatoru'
            ],
            '99' => [
                'name' => 'Bakcell',
                'type' => 'mobile',
                'formats' => ['99XXXXXXX'],
                'description' => 'Bakcell mobil operatoru'
            ],
            '70' => [
                'name' => 'Nar',
                'type' => 'mobile',
                'formats' => ['70XXXXXXX'],
                'description' => 'Nar mobil operatoru'
            ],
            '77' => [
                'name' => 'Nar',
                'type' => 'mobile',
                'formats' => ['77XXXXXXX'],
                'description' => 'Nar mobil operatoru'
            ],
        ],
        'landline' => [
            '12' => [
                'name' => 'Baku',
                'type' => 'landline',
                'formats' => ['12XXXXXXX'],
                'description' => 'Bakı şəhər telefonu'
            ],
            '22' => [
                'name' => 'Ganja',
                'type' => 'landline',
                'formats' => ['22XXXXXXX'],
                'description' => 'Gəncə şəhər telefonu'
            ],
        ]
    ];

    /**
     * Validasiya xəta mesajları
     */
    protected const ERROR_MESSAGES = [
        'invalid_format' => 'Telefon nömrəsi düzgün formatda deyil. Düzgün format: +994 XX XXX XX XX',
        'missing_prefix' => 'Telefon nömrəsi +994 ilə başlamalıdır',
        'invalid_operator' => 'Daxil edilən operator kodu (:code) düzgün deyil',
        'mobile_only' => 'Yalnız mobil operator nömrələrinə icazə verilir. Daxil edilən nömrə :operator operatoruna aiddir',
        'landline_only' => 'Yalnız şəhər nömrələrinə icazə verilir. Daxil edilən nömrə :operator operatoruna aiddir',
        'unknown_operator' => 'Naməlum operator kodu: :code',
        'invalid_length' => 'Telefon nömrəsi :length rəqəmdən ibarət olmalıdır',
        'required_format' => 'Telefon nömrəsi :format formatında olmalıdır'
    ];

    /**
     * Konfiqurasiya parametrləri
     */
    protected bool $autoPrefix;
    protected bool $mobileOnly;
    protected bool $landlineOnly;
    protected bool $strictFormat;
    protected string $defaultRegion = 'AZ';
    protected string $defaultFormat = 'E164'; // E164, INTERNATIONAL, NATIONAL

    public function __construct(array $config = [])
    {
        $this->autoPrefix = $config['autoPrefix'] ?? true;
        $this->mobileOnly = $config['mobileOnly'] ?? false;
        $this->landlineOnly = $config['landlineOnly'] ?? false;
        $this->strictFormat = $config['strictFormat'] ?? true;

        // Mobile və landline eyni anda seçilə bilməz
        if ($this->mobileOnly && $this->landlineOnly) {
            throw new \InvalidArgumentException('Cannot require both mobile and landline numbers');
        }
    }

    /**
     * Validasiya qaydalarını işə salır
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Telefon nömrəsini təmizləyirik
        $phone = $this->cleanPhone($value);

        // Nömrəni validate edirik və xətaları yoxlayırıq
        try {
            $this->validatePhone($phone, $fail);
        } catch (\Exception $e) {
            $fail($e->getMessage());
            return;
        }

        // Nömrəni format edirik və request-ə mənimsədirik
        if ($this->isRequestAvailable()) {
            request()->merge([$attribute => $this->formatPhone($phone)]);
        }
    }

    /**
     * Telefon nömrəsinin əsas validasiyası
     */
    protected function validatePhone(string $phone, Closure $fail): void
    {
        // Ümumi format yoxlaması
        if (!$this->isValidFormat($phone)) {
            throw new \Exception($this->getErrorMessage('invalid_format'));
        }

        // Prefiks yoxlaması
        if (!$this->hasValidPrefix($phone)) {
            throw new \Exception($this->getErrorMessage('missing_prefix'));
        }

        // Operator kodunu yoxlayırıq
        $operatorCode = $this->getOperatorCode($phone);
        $operatorInfo = $this->getOperatorInfo($operatorCode);

        if (!$operatorInfo) {
            throw new \Exception($this->getErrorMessage('unknown_operator', [
                'code' => $operatorCode
            ]));
        }

        // Mobil/Landline yoxlamaları
        if ($this->mobileOnly && $operatorInfo['type'] !== 'mobile') {
            throw new \Exception($this->getErrorMessage('mobile_only', [
                'operator' => $operatorInfo['name']
            ]));
        }

        if ($this->landlineOnly && $operatorInfo['type'] !== 'landline') {
            throw new \Exception($this->getErrorMessage('landline_only', [
                'operator' => $operatorInfo['name']
            ]));
        }
    }

    /**
     * Telefon nömrəsini təmizləyir
     */
    protected function cleanPhone(string $phone): string
    {
        // Boşluqları və xüsusi simvolları təmizləyirik
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        // Avtomatik prefiks əlavəsi
        if ($this->autoPrefix && !str_starts_with($phone, '+994')) {
            $phone = ltrim($phone, '0');
            // Əgər 994-lə başlayırsa + əlavə edirik
            if (str_starts_with($phone, '994')) {
                $phone = '+' . $phone;
            } else {
                $phone = '+994' . $phone;
            }
        }

        return $phone;
    }

    /**
     * Nömrəni formatlaşdırır
     */
    protected function formatPhone(string $phone): string
    {
        // Format seçimindən asılı olaraq nömrəni formatlaşdırırıq
        return match ($this->defaultFormat) {
            'INTERNATIONAL' => preg_replace(
                '/^\+994(\d{2})(\d{3})(\d{2})(\d{2})$/',
                '+994 $1 $2 $3 $4',
                $phone
            ),
            'NATIONAL' => preg_replace(
                '/^\+994(\d{2})(\d{3})(\d{2})(\d{2})$/',
                '0$1 $2 $3 $4',
                $phone
            ),
            default => $phone // E164 format
        };
    }

    /**
     * Ümumi formatı yoxlayır
     */
    protected function isValidFormat(string $phone): bool
    {
        return strlen($phone) === 13 && preg_match('/^\+994\d{9}$/', $phone);
    }

    /**
     * Prefiksi yoxlayır
     */
    protected function hasValidPrefix(string $phone): bool
    {
        return str_starts_with($phone, '+994');
    }

    /**
     * Operator kodunu əldə edir
     */
    protected function getOperatorCode(string $phone): string
    {
        return substr($phone, 4, 2);
    }

    /**
     * Operator haqqında məlumat qaytarır
     */
    protected function getOperatorInfo(string $code): ?array
    {
        foreach (self::OPERATORS as $type => $operators) {
            if (isset($operators[$code])) {
                return array_merge(
                    $operators[$code],
                    ['code' => $code]
                );
            }
        }

        return null;
    }

    /**
     * Xəta mesajını qaytarır
     */
    protected function getErrorMessage(string $type, array $params = []): string
    {
        $message = self::ERROR_MESSAGES[$type] ?? 'Yanlış telefon nömrəsi formatı';

        foreach ($params as $key => $value) {
            $message = str_replace(":$key", $value, $message);
        }

        return $message;
    }

    /**
     * Request obyektinin mövcudluğunu yoxlayır
     */
    protected function isRequestAvailable(): bool
    {
        return function_exists('request');
    }

    /**
     * Factory metodlar
     */
    public static function mobile(): self
    {
        return new static(['mobileOnly' => true]);
    }

    public static function landline(): self
    {
        return new static(['landlineOnly' => true]);
    }

    public static function strict(): self
    {
        return new static(['strictFormat' => true, 'autoPrefix' => false]);
    }

    public static function flexible(): self
    {
        return new static(['strictFormat' => false, 'autoPrefix' => true]);
    }

    public static function format(string $format): self
    {
        return new static(['defaultFormat' => $format]);
    }
}
