<?php

namespace App\Models;

use App\Enums\NotificationDeviceTypeEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute as AttributeAlias;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationDevice extends Model
{
    /**
     * Kütləvi təyin edilə bilən atributlar.
     * @var array
     */
    protected $fillable = [
        'user_id',
        'device_token',
        'device_type',
        'device_name',
        'app_version',
        'is_active',
        'last_used_at'
    ];

    /**
     * Verilənlər tipini çevrilməli olan atributlar.
     * @var array
     */
    protected $casts = [
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
    ];

    /**
     * Avtomatik əlavə edilən atributlar.
     * @var array
     */
    protected $appends = ['device_type_text'];

    /**
     * Cihaz növünün mətn təsvirini qaytarır.
     * @return AttributeAlias
     */
    public function getDeviceTypeTextAttribute(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                return $this->device_type ? NotificationDeviceTypeEnum::getDescription($this->device_type) : null;
            }
        );
    }

    /**
     * Bildiriş cihazına aid istifadəçi əlaqəsi.
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Cihazı aktiv kimi işarələyir və son istifadə vaxtını yeniləyir.
     * @return bool
     */
    public function markAsUsed(): bool
    {
        $this->is_active = true;
        $this->last_used_at = now();
        return $this->save();
    }

    /**
     * Cihazı deaktiv edir.
     * @return bool
     */
    public function deactivate(): bool
    {
        $this->is_active = false;
        return $this->save();
    }

    /**
     * Aktiv cihazları axtarış.
     * @param Builder $query
     * @return Builder
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Müəyyən növ cihazları axtarış.
     * @param Builder $query
     * @param string $type
     * @return Builder
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('device_type', $type);
    }

    /**
     * iOS cihazları axtarış.
     * @param Builder $query
     * @return Builder
     */
    public function scopeIos(Builder $query): Builder
    {
        return $query->where('device_type', 'ios');
    }

    /**
     * Android cihazları axtarış.
     * @param Builder $query
     * @return Builder
     */
    public function scopeAndroid(Builder $query): Builder
    {
        return $query->where('device_type', 'android');
    }

    /**
     * Web cihazları axtarış.
     * @param Builder $query
     * @return Builder
     */
    public function scopeWeb(Builder $query): Builder
    {
        return $query->where('device_type', 'web');
    }

    /**
     * Son dövrün cihazlarını axtarış.
     * @param Builder $query
     * @param int $days
     * @return Builder
     */
    public function scopeRecentlyUsed(Builder $query, int $days = 30): Builder
    {
        return $query->whereNotNull('last_used_at')
            ->where('last_used_at', '>=', now()->subDays($days));
    }
}
