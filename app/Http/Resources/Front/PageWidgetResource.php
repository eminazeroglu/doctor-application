<?php

namespace App\Http\Resources\Front;

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
            'type' => $this->type,
            'order' => $this->order,
            'data' => $this->data,
            'title' => $this->title,
            'description' => $this->description,
            'button_text' => $this->button_text,
            'photo' => $this->when($this->photo_path, $this->photo),
            'photo_urls' => $this->when($this->photo_path, $this->getAllImageUrls('photo_path'))
        ];
    }
}
