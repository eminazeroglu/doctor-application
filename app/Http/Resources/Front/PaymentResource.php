<?php

namespace App\Http\Resources\Front;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
       // return parent::toArray($request);
        return [
            'uuid' => $this->uuid,
            'related_entity' => $this->when($this->relationLoaded('paymentable'), $this->related_entity),
            'amount' => $this->amount,
            'currency' => $this->currency,
            'status' => $this->status,
            'status_text' => $this->status_text,
            'payment_method' => $this->payment_method,
            'payment_method_text' => $this->payment_method_text,
            'paid_at' => $this->paid_at,
            'description' => $this->description,
            'created_at' => $this->created_at,
        ];
    }
}
