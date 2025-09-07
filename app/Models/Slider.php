<?php

namespace App\Models;

use App\Traits\Model\HasImage;
use App\Traits\Model\HasTranslate;

class Slider extends BaseModel
{
    use HasTranslate, HasImage;
    protected $fillable = [
        'translates',
        'photo_path',
        'is_active'
    ];

    protected $appends = ['photo'];

    public function getTranslatableAttributes(): array
    {
        return [
            'title',
            'description',
            'button_text',
            'button_link',
        ];
    }

    public function getImageFields(): array
    {
        return [
            'photo_path' => [
                'path' => 'slider',
                'base64' => true,
            ]
        ];
    }
}
