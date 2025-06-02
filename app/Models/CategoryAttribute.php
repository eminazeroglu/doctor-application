<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class CategoryAttribute extends Pivot
{
    public $incrementing = true;

    protected $table = 'category_attribute';

    protected $fillable = [
        'category_id',
        'attribute_id',
        'is_required',
        'is_visible',
        'order',
        'validation_rules',
        'custom_fields',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_visible' => 'boolean',
        'validation_rules' => 'array',
        'custom_fields' => 'object'
    ];

    // Əlaqələr - pivot model vasitəsilə birbaşa category və attribute-a çata bilərik
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }
}
