<?php

namespace App\Models;

use App\Enums\AttributePositionEnum;
use App\Enums\AttributeTypeEnum;
use App\Traits\Model\HasLoggable;
use App\Traits\Model\HasTranslate;
use Illuminate\Database\Eloquent\Casts\Attribute as AttributeCast;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attribute extends BaseModel
{
    use HasTranslate, HasLoggable;

    protected $fillable = [
        'uuid',
        'slug',
        'translates',
        'type',
        'parent_id',
        'group_name',
        'is_active',
        'order',
        'custom_fields'
    ];

    protected $casts = [
        'translates' => 'object',
        'custom_fields' => 'object',
        'is_active' => 'boolean'
    ];

    protected $appends = ['type_text', 'has_dependent_options'];

    public function getTranslatableAttributes(): array
    {
        return [
            'name',
            'description',
        ];
    }

    // Əlaqələr
    public function options(): HasMany
    {
        return $this->hasMany(AttributeOption::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_attribute')
            ->withPivot(['validation_rules', 'is_required', 'is_visible', 'order', 'custom_fields'])
            ->withTimestamps();
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Attribute::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Attribute::class, 'parent_id');
    }

    // Attributes

    public function typeText(): AttributeCast
    {
        return new AttributeCast(
            get: fn() => AttributeTypeEnum::getDescription($this->type)
        );
    }

    protected function hasDependentOptions(): AttributeCast
    {
        return AttributeCast::make(
            get: function () {
                return $this->parent()->exists();
            }
        );
    }
}
