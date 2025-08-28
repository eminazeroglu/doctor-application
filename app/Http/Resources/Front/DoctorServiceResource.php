<?php

namespace App\Http\Resources\Front;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DoctorServiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        //return parent::toArray($request);
        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => $this->pivot->price,
            'duration' => $this->pivot->duration,
            'description' => $this->pivot->description,
            'clinic_id' => $this->pivot->clinic_id
        ];
    }
}
