<?php

namespace App\Models;

use App\Traits\Model\HasImage;
use App\Traits\Model\HasTranslate;

class Testimonial extends BaseModel
{
    use HasTranslate, HasImage;

    protected $fillable = [
        'translates',
        'photo_path',
        'rating',
        'is_active',
    ];

    protected $appends = ['photo'];

    public function getTranslatableAttributes(): array
    {
        return [
            'fullname',
            'profession',
            'comment',
        ];
    }

    public function getImageFields(): array
    {
        return [
            'photo_path' => [
                'path' => 'testimonial',
                'base64' => true,
            ]
        ];
    }
}
