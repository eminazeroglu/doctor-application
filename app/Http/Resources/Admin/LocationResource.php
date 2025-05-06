<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LocationResource extends JsonResource
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
            'name' => $this->name,
            'uuid' => $this->uuid,
            'slug' => $this->slug,
            'cities' => LocationResource::collection($this->whenLoaded('cities')),
            'regions' => LocationResource::collection($this->whenLoaded('regions')),
            'subways' => LocationResource::collection($this->whenLoaded('subways')),
        ];
    }
}
