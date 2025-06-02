<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryAttributeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        //return parent::toArray($request);

        $full = request()->has('full');

        return [
            'id' => $this->when($full, $this->id),
            'order' => $this->when($full, $this->order),
            'attribute_id' => $this->when($full, $this->attribute_id),
            'label' => $this->attribute->name,
            'description' => $this->attribute->description,
            'is_required' => $this->is_required,
            'is_visible' => $this->is_visible,
            'type' => $this->attribute->type,
            'type_text' => $this->when($full, $this->attribute->type_text),
            'options' => $this->when(($this->attribute->options && count($this->attribute->options) > 0), collect($this->attribute->options)->map(fn($i) => [
                'id' => $i->id,
                'label' => $i->name,
                'is_default' => $i->is_default,
            ]))
        ];
    }
}
