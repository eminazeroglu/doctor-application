<?php
// app/Models/Page.php

namespace App\Models;

use App\Enums\PageTypeEnum;
use App\Traits\Model\HasImage;
use App\Traits\Model\HasTranslate;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Page extends BaseModel
{
    use HasTranslate, HasImage, SoftDeletes;

    protected $fillable = [
        'slug',
        'is_active',
        'is_system',
        'type',
        'translates',
        'photo_path'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_system' => 'boolean'
    ];

    protected $appends = ['photo'];

    public function getTranslatableAttributes(): array
    {
        return ['name', 'content'];
    }

    // Slugı avtomatik olaraq addan yaradacaq
    protected function getSlugSourceColumn(): string
    {
        return 'name';
    }

    public function getImageFields(): array
    {
        return [
            'photo_path' => [
                'path' => 'pages',
                'base64' => true,
            ]
        ];
    }

    // Tip mətni accessor-u
    protected function typeText(): Attribute
    {
        return Attribute::make(
            get: fn () => PageTypeEnum::getDescription($this->type),
        );
    }

    // Widget əlaqəsi
    public function widgets(): HasMany
    {
        return $this->hasMany(PageWidget::class)->orderBy('order');
    }

    // Widget əlavə etmək
    public function addWidget(array $data): PageWidget
    {
        return $this->widgets()->create($data);
    }
}
