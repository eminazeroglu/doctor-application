<?php

namespace App\Http\Resources\Front;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BlogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
       // return parent::toArray($request);
        return [
            'slug' => $this->slug,
            'category' => [
                'slug' => $this->category->slug,
                'name' => $this->category->name,
            ],
            'title' => $this->title,
            'description' => $this->description,
            'photo' => $this->photo,
            'created_at' => $this->created_at,
        ];
    }
}
