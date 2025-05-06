<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserBlockResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $blocker = $this->resource->blocker;
        $blocked = $this->resource->blocked;

        return [
            'id' => $this->resource->id,
            'uuid' => $this->resource->uuid,
            'blocker' => [
                'id' => $blocker->id,
                'name' => $blocker->name,
                'surname' => $blocker->surname,
                'fullname' => $blocker->name . ' ' . $blocker->surname,
                'email' => $blocker->email,
                'photo' => $blocker->photo
            ],
            'blocked' => [
                'id' => $blocked->id,
                'name' => $blocked->name,
                'surname' => $blocked->surname,
                'fullname' => $blocked->name . ' ' . $blocked->surname,
                'email' => $blocked->email,
                'photo' => $blocked->photo
            ],
            'reason' => $this->resource->reason,
            'meta_data' => $this->resource->meta_data,
            'created_at' => $this->resource->created_at,
            'deleted_at' => $this->resource->deleted_at,
            'is_active' => $this->resource->deleted_at === null,
            // Admin əlavə məlumatları
            'admin_action' => $this->resource->meta_data['admin_action'] ?? false,
            'admin_id' => $this->resource->meta_data['admin_id'] ?? null,
            'admin_notes' => $this->resource->meta_data['admin_notes'] ?? null,
            'blocked_at' => $this->resource->meta_data['blocked_at'] ?? null,
            'blocker_ip' => $this->resource->meta_data['blocker_ip'] ?? null,
            'unblocked_at' => $this->resource->meta_data['unblocked_at'] ?? null
        ];
    }
}
