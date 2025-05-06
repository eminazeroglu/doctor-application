<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PageResource extends JsonResource
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
            'slug' => $this->slug,
            'type' => $this->type,
            'type_text' => $this->typeText,
            'is_active' => $this->is_active,
            'is_system' => $this->is_system,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'translates' => $this->translates,
            'name' => $this->name,
            'content' => $this->content,
            'photo' => $this->photo,
            'photo_urls' => $this->getAllImageUrls('photo_path'),
            'widgets' => PageWidgetResource::collection($this->whenLoaded('widgets'))
        ];
    }
}
