<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttributeTreeResource extends JsonResource
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
            'parent_id' => $this->parent_id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'slug' => $this->slug,
            'meta_tags' => $this->meta_tags,
            'position' => $this->position,
            'position_text' => $this->position_text,
            'type' => $this->type,
            'type_text' => $this->type_text,
            'is_active' => $this->is_active,
            'order' => $this->order,
            'custom_fields' => $this->custom_fields,
            'has_dependent_options' => $this->has_dependent_options,
        ];

        // Children-i şərti olaraq əlavə edirik
        if ($this->whenLoaded('children') && $this->children->isNotEmpty()) {
            $data['children'] = AttributeTreeResource::collection($this->children);
        }

        return $data;
    }
}
