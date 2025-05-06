<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;


class AuthResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'fullname' => $this->fullname,
            'name' => $this->name,
            'surname' => $this->surname,
            'username' => $this->username,
            'code' => $this->code,
            'email' => $this->email,
            'gender' => $this->gender,
            'gender_text' => $this->gender_text,
            'phone' => $this->phone,
            'photo' => $this->getImageUrl('photo_path'),
            'has_photo' => $this->photo_path,
            'status' => $this->status,
            'status_text' => $this->status_text,
            'role' => $this->role->group_name,
            $this->mergeWhen(count($this->getAllPermissions()) > 0, [
                'permissions' => $this->getAllPermissions()->pluck('name'),
            ]),
            $this->mergeWhen($this->relationLoaded('preferences') && $this->preferences !== null, [
                'preferences' => new UserPreferenceResource($this->preferences),
            ]),
            'monitoring' => $this->when($this->hasPermission('config_monitoring'), url(env('TELESCOPE_PATH') . '?uuid=' . $this->uuid))
        ];
    }
}
