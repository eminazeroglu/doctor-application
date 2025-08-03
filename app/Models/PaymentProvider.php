<?php

namespace App\Models;

use App\Traits\Model\HasImage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentProvider extends BaseModel
{
    use HasImage;
    /**
     * Kütləvi təyin edilə bilən atributlar.
     * @var array
     */
    protected $fillable = [
        'name',
        'code',
        'description',
        'photo_path',
        'configuration',
        'is_active',
        'is_test_mode'
    ];

    /**
     * Verilənlər tipini çevrilməli olan atributlar.
     * @var array
     */
    protected $casts = [
        'configuration' => 'json',
        'is_active' => 'boolean',
        'is_test_mode' => 'boolean',
    ];

    /**
     * Avtomatik əlavə edilən atributlar.
     * @var array
     */
    protected $appends = ['photo'];


    /**
     * Təchizatçıya aid loglar əlaqəsi.
     * @return HasMany
     */
    public function logs(): HasMany
    {
        return $this->hasMany(PaymentProviderLog::class);
    }

    /**
     * Konfiqurasiya dəyərini qaytarır.
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    public function getConfig(string $key, mixed $default = null): mixed
    {
        if (!$this->configuration) {
            return $default;
        }

        return $this->configuration[$key] ?? $default;
    }

    /**
     * Konfiqurasiya dəyərini təyin edir.
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public function setConfig(string $key, mixed $value): bool
    {
        $config = $this->configuration ?? [];
        $config[$key] = $value;
        $this->configuration = $config;

        return $this->save();
    }

    /**
     * Aktiv təchizatçıları axtarış.
     * @param Builder $query
     * @return Builder
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Test rejimində olan təchizatçıları axtarış.
     * @param Builder $query
     * @return Builder
     */
    public function scopeTestMode(Builder $query): Builder
    {
        return $query->where('is_test_mode', true);
    }

    /**
     * Canlı rejimində olan təchizatçıları axtarış.
     * @param Builder $query
     * @return Builder
     */
    public function scopeLiveMode(Builder $query): Builder
    {
        return $query->where('is_test_mode', false);
    }

    /**
     * Koda görə təchizatçını axtarış.
     * @param Builder $query
     * @param string $code
     * @return Builder
     */
    public function scopeWithCode(Builder $query, string $code): Builder
    {
        return $query->where('code', $code);
    }

    public function getImageFields(): array
    {
        return [
            'logo_path' => [
                'path' => 'payment-providers',
            ]
        ];
    }
}
