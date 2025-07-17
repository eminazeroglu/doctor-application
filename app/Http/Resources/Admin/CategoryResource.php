<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'slug' => $this->slug,
            'icon' => $this->icon,
            'photo' => $this->photo,
            'meta_tags' => $this->meta_tags,
            'is_default' => $this->is_default,
            'is_active' => $this->is_active,
            'is_home' => $this->is_home,
            'order' => $this->order,
            'custom_fields' => $this->custom_fields,
            'children' => CategoryResource::collection($this->whenLoaded('children')),
            'terms' => new ResourceCollection($this->whenLoaded('terms')),
        ];

        return $data;
    }
}
