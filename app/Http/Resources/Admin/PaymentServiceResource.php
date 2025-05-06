<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentServiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
       // return parent::toArray($request);
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'icon' => $this->icon,
            'options' => collect($this->options)->map(function ($option) {

                $label = str(t('enums.payment_service.' . $this->option_type))->replace(
                    [':count', ':amount'],
                    [$option->value, $option->amount]
                );

                return [
                    'id' => $option->id,
                    'label' => $label,
                ];
            })
        ];
    }
}
