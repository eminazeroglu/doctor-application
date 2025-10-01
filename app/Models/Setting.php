<?php

namespace App\Models;

use App\Traits\Model\HasImage;
use App\Traits\Model\HasLoggable;

class Setting extends BaseModel
{
    use HasLoggable, HasImage;

    protected $fillable = [
        'key',
        'values'
    ];

    protected $casts = [
        'values' => 'json'
    ];

    // Setting-i key ilə tapmaq üçün scope
    public function scopeByKey($query, string $key)
    {
        return $query->where('key', $key);
    }

    // Konkret dəyəri əldə etmək üçün helper method
    public function getValue(string $path = null, $default = null)
    {
        if ($path === null) {
            return $this->values;
        }

        return data_get($this->values, $path, $default);
    }

    // Dəyəri yeniləmək üçün helper method
    public function setValue(string $path, $value): static
    {
        $values = $this->values;
        data_set($values, $path, $value);
        $this->values = $values;
        return $this;
    }

    public function getImageFields(): array
    {
        return [
            'logo' => [
                'path' => 'setting',
            ],
            'logo_dark' => [
                'path' => 'setting',
            ],
            'mobile_logo' => [
                'path' => 'setting',
            ],
            'mobile_logo_dark' => [
                'path' => 'setting',
            ],
            'favicon' => [
                'path' => 'setting',
            ],
            'wallpaper' => [
                'path' => 'setting',
            ],
            'join_us_wallpaper' => [
                'path' => 'setting',
            ],
            'app_qr' => [
                'path' => 'setting',
            ]
        ];
    }
}
