<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SettingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Əgər koleksiya olaraq istifadə olunursa
        if (is_array($this->resource)) {
            return [
                'key' => key($this->resource),
                'values' => current($this->resource)
            ];
        }

        // Tək setting üçün
        return [
            'key' => $this->key,
            'values' => $this->values,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            // Audit məlumatları
            'created_by' => $this->created_by_name,
            'updated_by' => $this->updated_by_name,
        ];
    }
}
