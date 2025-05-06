<?php

namespace App\Models;

use App\Traits\Model\HasImage;
use App\Traits\Model\HasSeoLink;
use App\Traits\Model\HasTranslate;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends BaseModel
{
    use HasImage, HasTranslate, HasSeoLink;

    protected $fillable = [
        'uuid',
        'slug',
        'parent_id',
        'terms_id',
        'translates',
        'icon',
        'photo_path',
        'meta_tags',
        'custom_fields',
        'is_default',
        'is_active',
        'order'
    ];

    protected $casts = [
        'meta_tags' => 'array',
        'custom_fields' => 'object',
        'is_default' => 'boolean',
        'is_active' => 'boolean'
    ];

    protected $appends = ['photo'];

    // Relationships
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }
    public function getImageFields(): array
    {
        return [
            'photo_path' => [
                'path' => 'category',
                'thumbnail' => true,
                'watermark' => true,
                'base64' => true,
            ]
        ];
    }

    public function getTranslatableAttributes(): array
    {
        return [
            'name',
            'description',
        ];
    }
}
