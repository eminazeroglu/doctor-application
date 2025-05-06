<?php

namespace App\Http\Resources\Front;

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
            'name' => $this->name,
            'content' => $this->content,
            'photo' => $this->when($this->photo_path, $this->photo),
            'photo_urls' => $this->when($this->photo_path, $this->getAllImageUrls('photo_path')),
            'widgets' => PageWidgetResource::collection($this->whenLoaded('widgets')),
            'updated_at' => $this->updated_at
        ];
    }
}
