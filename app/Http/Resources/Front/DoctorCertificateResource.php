<?php

namespace App\Http\Resources\Front;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DoctorCertificateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'doctor_id' => $this->doctor_id,
            'name' => $this->name,
            'issuing_organization' => $this->issuing_organization,
            'issue_date' => $this->issue_date ? $this->issue_date->format('Y-m-d') : null,
            'expiry_date' => $this->expiry_date ? $this->expiry_date->format('Y-m-d') : null,
            'description' => $this->description,
            'document_path' => $this->document_path,
            'is_verified' => $this->is_verified,

            // Computed attributes
            'is_expired' => $this->is_expired,
            'document_url' => $this->document_url,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
