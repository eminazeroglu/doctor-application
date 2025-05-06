<?php
// app/Models/PageWidget.php

namespace App\Models;

use App\Enums\WidgetTypeEnum;
use App\Traits\Model\HasImage;
use App\Traits\Model\HasTranslate;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PageWidget extends BaseModel
{
    use HasTranslate, HasImage, SoftDeletes;

    protected $fillable = [
        'page_id',
        'type',
        'data',
        'order',
        'is_active',
        'photo_path',
        'translates'
    ];

    protected $casts = [
        'data' => 'json',
        'is_active' => 'boolean'
    ];

    public function getTranslatableAttributes(): array
    {
        return ['title', 'description', 'button_text'];
    }

    public function getImageFields(): array
    {
        return [
            'photo_path' => [
                'path' => 'widgets',
                'base64' => true,
            ]
        ];
    }

    // Tip mətni accessor-u
    protected function typeText(): Attribute
    {
        return Attribute::make(
            get: fn () => WidgetTypeEnum::getDescription($this->type),
        );
    }

    // Əlaqələr
    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }
}
