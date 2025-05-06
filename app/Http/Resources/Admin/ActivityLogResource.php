<?php

namespace App\Http\Resources\Admin;

use App\Enums\ActivityLogStatusEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityLogResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'action' => [
                'key' => $this->action,
                'label' => $this->action_description,
            ],
            'status' => [
                'key' => $this->status,
                'label' => $this->status_description,
            ],
            'model' => $this->when($this->model_type, [
                'type' => $this->model_type,
                'id' => $this->model_id,
            ]),
            'changes' => $this->when($this->old_data || $this->new_data, $this->getChanges()),
            'meta' => [
                'ip_address' => $this->ip_address,
                'user_agent' => $this->user_agent,
                'method' => $this->method,
                'url' => $this->url,
                'browser_info' => $this->meta_data['browser'] ?? null,
                'platform_info' => $this->meta_data['platform'] ?? null,
                'device_info' => $this->meta_data['device'] ?? null,
                'viewed_by' => $this->meta_data['viewed_by'] ?? null,
                'view_duration' => $this->meta_data['view_duration'] ?? null,
                'device_id' => $this->meta_data['device_id'] ?? null,
                'session_duration' => $this->meta_data['session_duration'] ?? null,
            ],
            'error_message' => $this->when($this->status === ActivityLogStatusEnum::ERROR, $this->error_message),
            'creator' => new UserResource($this->whenLoaded('creator')),
            'created_at' => $this->created_at,
        ];
    }
}
