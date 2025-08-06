<?php

namespace App\Models;

use App\Traits\Model\HasImage;
use App\Traits\Model\HasTranslate;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Blog extends BaseModel
{
    use HasImage, HasTranslate;

    protected $fillable = [
        'translates',
        'slug',
        'uuid',
        'category_id',
        'photo_path',
        'is_active',
    ];

    protected $appends = ['photo'];

    // Add any additional methods or relationships here
    public function getImageFields(): array
    {
        return [
            'photo_path' => [
                'path' => 'blog',
                'base64' => true
            ]
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function getTranslatableAttributes(): array
    {
        return [
            'title',
            'description',
            'content',
        ];
    }
}
