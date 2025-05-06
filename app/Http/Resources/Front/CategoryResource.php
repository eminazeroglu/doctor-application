<?php

namespace App\Http\Resources\Front;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        //return parent::toArray($request);
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'slug' => $this->slug,
            'photo' => $this->photo,
            'listings_count' => $this->whenCounted('listings'),
            'active_listings_count' => $this->whenCounted('active_listings_count'),
            'children' => CategoryResource::collection($this->whenLoaded('children')),
        ];
    }
}
