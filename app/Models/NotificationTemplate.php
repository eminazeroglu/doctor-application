<?php

namespace App\Models;

use App\Enums\NotificationChannelEnum;
use App\Enums\NotificationTemplateTypeEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute as AttributeAlias;
use Illuminate\Database\Eloquent\Model;

class NotificationTemplate extends Model
{
    /**
     * Kütləvi təyin edilə bilən atributlar.
     * @var array
     */
    protected $fillable = [
        'name',
        'code',
        'channel',
        'type',
        'subject',
        'content',
        'variables',
        'is_active'
    ];

    /**
     * Verilənlər tipini çevrilməli olan atributlar.
     * @var array
     */
    protected $casts = [
        'variables' => 'json',
        'is_active' => 'boolean',
    ];

    /**
     * Avtomatik əlavə edilən atributlar.
     * @var array
     */
    protected $appends = ['channel_text', 'type_text'];

    /**
     * Bildiriş kanalının mətn təsvirini qaytarır.
     * @return AttributeAlias
     */
    public function getChannelTextAttribute(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                return $this->channel ? NotificationChannelEnum::getDescription($this->channel) : null;
            }
        );
    }

    /**
     * Şablon növünün mətn təsvirini qaytarır.
     * @return AttributeAlias
     */
    public function typeText(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                return $this->type ? NotificationTemplateTypeEnum::getDescription($this->type) : null;
            }
        );
    }

    /**
     * Məzmunu göstərilən məlumatlarla doldurur.
     * @param array $data
     * @return string
     */
    public function parseContent(array $data): string
    {
        $content = $this->content;

        foreach ($data as $key => $value) {
            $content = str_replace('{' . $key . '}', $value, $content);
        }

        return $content;
    }

    /**
     * Mövzunu göstərilən məlumatlarla doldurur.
     * @param array $data
     * @return string|null
     */
    public function parseSubject(array $data): ?string
    {
        if (!$this->subject) {
            return null;
        }

        $subject = $this->subject;

        foreach ($data as $key => $value) {
            $subject = str_replace('{' . $key . '}', $value, $subject);
        }

        return $subject;
    }

    /**
     * Kanala görə şablonları axtarış.
     * @param Builder $query
     * @param string $channel
     * @return Builder
     */
    public function scopeForChannel(Builder $query, string $channel): Builder
    {
        return $query->where('channel', $channel);
    }

    /**
     * Növə görə şablonları axtarış.
     * @param Builder $query
     * @param string $type
     * @return Builder
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Koda görə şablonu axtarış.
     * @param Builder $query
     * @param string $code
     * @return Builder
     */
    public function scopeWithCode(Builder $query, string $code): Builder
    {
        return $query->where('code', $code);
    }

    /**
     * Aktiv şablonları axtarış.
     * @param Builder $query
     * @return Builder
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
