<?php

namespace App\Http\Resources\Front;

use App\Http\Resources\Admin\PatientResource;
use App\Http\Resources\Admin\UserPreferenceResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * İstifadəçi resursu - Profile settings üçün
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'surname' => $this->surname,
            'fullname' => $this->fullname,
            'email' => $this->email,
            'phone' => $this->phone,
            'gender' => $this->gender,
            'gender_text' => $this->gender_text,
            'birthdate' => $this->birthdate,
            'status' => $this->status,
            'status_text' => $this->status_text,
            'user_type' => $this->user_type,
            'photo' => $this->photo, // HasImage trait-dən
            'photo_path' => $this->photo_path,
            'email_verified_at' => $this->email_verified_at,
            'main_balance' => $this->main_balance,
            'referral_balance' => $this->referral_balance,
            'referral_code' => $this->referral_code,
            'language' => $this->language,
            'is_system' => $this->is_system,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Relations
            'preferences' => new UserPreferenceResource($this->whenLoaded('preferences')),
            'doctor' => new DoctorResource($this->whenLoaded('doctor')),
            'patient' => new PatientResource($this->whenLoaded('patient')),

            // Additional computed fields
            'has_doctor' => $this->hasDoctor(),
            'has_profile_completed' => $this->hasProfileCompleted(),
            'profile_completion_percentage' => $this->getProfileCompletionPercentage(),
        ];
    }

    /**
     * Profil tamamlanıb-tamamlanmadığını yoxlayır
     */
    private function hasProfileCompleted(): bool
    {
        return !empty($this->name) &&
            !empty($this->surname) &&
            !empty($this->phone) &&
            !empty($this->photo_path);
    }

    /**
     * Profil tamamlanma faizini hesablayır
     */
    private function getProfileCompletionPercentage(): int
    {
        $fields = [
            'name' => !empty($this->name),
            'surname' => !empty($this->surname),
            'phone' => !empty($this->phone),
            'photo_path' => !empty($this->photo_path),
            'address' => !empty($this->address),
            'birthdate' => !empty($this->birthdate),
            'gender' => !empty($this->gender)
        ];

        $completedFields = array_filter($fields);
        return round((count($completedFields) / count($fields)) * 100);
    }
}
