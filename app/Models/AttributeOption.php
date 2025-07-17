<?php

namespace App\Models;

use App\Traits\Model\HasSlug;
use App\Traits\Model\HasTranslate;
use App\Traits\Model\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttributeOption extends Model
{
    use HasUuid, HasSlug, HasTranslate;

    protected $fillable = [
        'uuid',
        'slug',
        'attribute_id',
        'parent_id',
        'translates',
        'custom_fields',
        'is_default',
        'is_active',
        'order',
    ];

    protected $casts = [
        'translates' => 'object',
        'custom_fields' => 'object',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function getTranslatableAttributes(): array
    {
        return [
            'name'
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(AttributeOption::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(AttributeOption::class, 'parent_id');
    }

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class, 'attribute_id');
    }
}
