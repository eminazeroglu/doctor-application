<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PageWidgetResource extends JsonResource
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
            'page_id' => $this->page_id,
            'type' => $this->type,
            'type_text' => $this->typeText,
            'order' => $this->order,
            'data' => $this->data,
            'is_active' => $this->is_active,
            'photo' => $this->photo,
            'photo_urls' => $this->getAllImageUrls('photo_path'),
            'translates' => $this->translates,
            'title' => $this->title,
            'description' => $this->description,
            'button_text' => $this->button_text,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
