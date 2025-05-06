<?php

namespace App\Http\Resources\Admin;

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
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'user' => new UserResource($this->whenLoaded('user')),
            'paymentable_type' => $this->paymentable_type,
            'paymentable_id' => $this->paymentable_id,
            'related_entity' => $this->when($this->relationLoaded('paymentable'), $this->related_entity),
            'amount' => $this->amount,
            'currency' => $this->currency,
            'status' => $this->status,
            'status_text' => $this->status_text,
            'payment_method' => $this->payment_method,
            'payment_method_text' => $this->payment_method_text,
            'transaction' => $this->transaction,
            'paid_at' => $this->paid_at,
            'description' => $this->description,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
