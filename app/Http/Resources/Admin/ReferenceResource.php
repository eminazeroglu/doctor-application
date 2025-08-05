<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReferenceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        if (request()->has('responseFull')) {
            return parent::toArray($request);
        }

        $result = [
            'id' => $this->id,
        ];
        if ($this?->user?->fullname)
            $result['name'] = $this->user->fullname;
        else if ($this->fullname)
            $result['name'] = $this->fullname;
        else if ($this->name)
            $result['name'] = $this->name;
        else if ($this->title)
            $result['name'] = $this->title;

        if ($this->code)
            $result['code'] = $this->code;
        if ($this->locale)
            $result['locale'] = $this->locale;
        if ($this->uuid)
            $result['uuid'] = $this->uuid;
        if ($this->slug)
            $result['slug'] = $this->slug;
        if ($this->photo_path)
            $result['photo'] = $this->photo;
        if ($this->relationLoaded('children') && $this->children && $this->children->isNotEmpty()) {
            $result['children'] = ReferenceResource::collection($this->children);
        }
        return $result;
    }
}
