<?php

namespace App\Models;

use App\Enums\AdvertisementDisplayTypeEnum;
use App\Enums\AdvertisementPositionEnum;
use App\Traits\Model\HasTranslate;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Advertisement extends BaseModel
{
    use HasTranslate;

    protected $fillable = [
        'uuid',
        'translates',
        'position',
        'expiry_date',
        'background_color',
        'display_type',
        'selected_categories',
    ];

    protected $casts = [
        'translates' => 'json',
        'selected_categories' => 'array',
    ];

    protected $appends = ['position_text', 'display_type_text'];

    public function getTranslatableImageFields(): array
    {
        return [
            'photo' => [
                'path' => 'advertisement',
                'base64' => true
            ]
        ];
    }

    // Add any additional methods or relationships here
    public function getTranslatableAttributes(): array
    {
        return [
            'link',
            'photo'
        ];
    }

    /**
     * @return Attribute
     */
    public function positionText(): Attribute
    {
        return new Attribute(
            get: fn () => $this->position ? AdvertisementPositionEnum::getDescription($this->position) : null
        );
    }

    /**
     * @return Attribute
     */
    public function displayTypeText(): Attribute
    {
        return new Attribute(
            get: fn () => $this->display_type ? AdvertisementDisplayTypeEnum::getDescription($this->display_type) : null
        );
    }
}
