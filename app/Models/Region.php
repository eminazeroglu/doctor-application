<?php

namespace App\Models;

use App\Traits\Model\HasTranslate;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Region extends BaseModel
{
    use HasTranslate;

    protected $fillable = [
        'uuid',
        'slug',
        'country_id',
        'city_id',
        'order',
        'translates',
        'map_location',
    ];

    protected $casts = [
        'translates' => 'json',
        'map_location' => 'json',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
    public function subways(): HasMany
    {
        return $this->hasMany(Subway::class);
    }

    public function getTranslatableAttributes(): array
    {
        return [
            'name'
        ];
    }
}
