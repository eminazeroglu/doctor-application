<?php

namespace App\Models;

use App\Enums\PaymentServiceKeyEnum;
use App\Enums\PaymentServiceOptionTypeEnum;
use App\Enums\PaymentServiceTypeEnum;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentService extends BaseModel
{
    protected $fillable = [
        'uuid',
        'slug',
        'key_name',
        'icon',
        'type',
        'option_type',
    ];

    protected $appends = ['type_text', 'option_type_text', 'name'];

    public function options(): HasMany
    {
        return $this->hasMany(PaymentServiceOption::class, 'payment_service_id');
    }

    /**
     * @return Attribute
     */
    public function typeText(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->type ? PaymentServiceTypeEnum::getDescription($this->type) : null,
        );
    }

    /**
     * @return Attribute
     */
    public function name(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->key_name ? PaymentServiceKeyEnum::getDescription($this->key_name) : null,
        );
    }

    /**
     * @return Attribute
     */
    public function optionTypeText(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->option_type ? PaymentServiceOptionTypeEnum::getDescription($this->option_type) : null,
        );
    }
}
