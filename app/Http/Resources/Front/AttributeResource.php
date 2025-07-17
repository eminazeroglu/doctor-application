<?php

namespace App\Http\Resources\Front;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttributeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        //return parent::toArray($request);
        return [
            'id' => $this->attribute->id,
            'uuid' => $this->attribute->uuid,
            'slug' => $this->attribute->slug,
            'name' => $this->attribute->name,
            'description' => $this->attribute->description,
            'is_required' => $this->is_required,
            'is_visible' => $this->is_visible,
            'type' => $this->attribute->type,
            'options' => $this->when($this->attribute->options && count($this->attribute->options) > 0, function () {
                return collect($this->attribute->options)->map(fn($i) => [
                    'id' => $i->id,
                    'slug' => $i->slug,
                    'name' => $i->name,
                ]);
            })
        ];
    }
}
