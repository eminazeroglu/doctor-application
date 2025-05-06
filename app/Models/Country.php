<?php

namespace App\Models;

use App\Traits\Model\HasTranslate;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends BaseModel
{
    use HasTranslate;

    protected $fillable = [
        'uuid',
        'slug',
        'translates',
        'phone_code',
        'order',
        'currency',
        'map_location',
    ];

    protected $casts = [
        'translates' => 'json',
        'map_location' => 'json',
    ];

    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }
    public function getTranslatableAttributes(): array
    {
        return [
            'name'
        ];
    }
}
