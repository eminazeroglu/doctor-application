<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttributeOptionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        //return parent::toArray($request);
        $data = [
            'id' => $this->id,
            'parent_id' => $this->parent_id,
            'parent' => $this->parent ? [
                'name' => $this->parent->name
            ] : null,
            'name' => $this->name,
            'translates' => $this->translates,
            'uuid' => $this->uuid,
            'has_dependent_options' => $this->children->isNotEmpty(),
            'order' => $this->order,
            'custom_fields' => $this->custom_fields,
            'is_active' => $this->is_active,
            'is_default' => $this->is_default,
        ];

        if ($this->whenLoaded('children') && $this->children->isNotEmpty()) {
            $data['children'] = AttributeOptionResource::collection($this->children);
        }

        return $data;
    }
}
