<?php

namespace App\Http\Resources\Front;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BlogViewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'category' => [
                'slug' => $this->category->slug,
                'name' => $this->category->name,
            ],
            'title' => $this->title,
            'description' => $this->description,
            'content' => $this->content,
            'photo' => $this->photo,
            'created_at' => $this->created_at,
        ];
    }
}
