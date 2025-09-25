<?php

namespace App\Http\Resources\Admin;

use App\Helpers\Helper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $setting = setting('info');



        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'login_as' => $setting['front_url'] . '/login-with-token/' . Helper::encrypt($this->uuid),
            'photo' => $this->photo,
            'code' => $this->code,
            'email' => $this->email,
            'otp_code' => $this->otp_code,
            'phone' => $this->phone,
            'fullname' => $this->fullname,
            'status' => $this->status,
            'status_text' => $this->status_text,
            'gender' => $this->gender,
            'gender_text' => $this->gender_text,
            'is_system' => $this->is_system,
            'role' => str($this->role->name)->title()
        ];
    }
}
