<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,

            // Əsas məlumatlar
            'comment' => $this->comment,
            'rating' => $this->rating,
            'rating_stars' => $this->rating_stars,

            // Status məlumatları
            'is_anonymous' => $this->is_anonymous,
            'is_verified' => $this->is_verified,
            'is_moderated' => $this->is_moderated,
            'is_active' => $this->is_active,
            'status' => $this->status,
            'author_name' => $this->author_name,

            // Statistika məlumatları
            'helpful_count' => $this->helpful_count,
            'helpful_count_formatted' => $this->helpful_count > 0 ? $this->helpful_count . ' faydalı' : 'Faydalı deyil',
            'reports_count' => $this->whenCounted('reports'),
            'responses_count' => $this->whenCounted('responses'),

            // Əlaqəli məlumatlar
            'patient' => $this->whenLoaded('patient', function() {
                return [
                    'id' => $this->patient->id,
                    'name' => $this->patient->name,
                    'surname' => $this->patient->surname,
                    'full_name' => $this->patient->full_name,
                    'photo' => $this->patient->photo,
                ];
            }),

            'doctor' => $this->whenLoaded('doctor', function() {
                return $this->doctor ? [
                    'id' => $this->doctor->id,
                    'name' => $this->doctor->name,
                    'surname' => $this->doctor->surname,
                    'full_name' => $this->doctor->full_name,
                    'photo' => $this->doctor->photo,
                    'specialty' => $this->doctor->specialty,
                ] : null;
            }),

            'clinic' => $this->whenLoaded('clinic', function() {
                return $this->clinic ? [
                    'id' => $this->clinic->id,
                    'name' => $this->clinic->name,
                    'logo' => $this->clinic->logo,
                    'address' => $this->clinic->address,
                    'phone' => $this->clinic->phone,
                ] : null;
            }),

            'appointment' => $this->whenLoaded('appointment', function() {
                return $this->appointment ? [
                    'id' => $this->appointment->id,
                    'uuid' => $this->appointment->uuid,
                    'appointment_date' => $this->appointment->appointment_date,
                    'status' => $this->appointment->status,
                ] : null;
            }),

            // Cavablar
            'responses' => $this->whenLoaded('responses', function() {
                return $this->responses->map(function($response) {
                    return [
                        'id' => $response->id,
                        'response' => $response->response,
                        'is_moderated' => $response->is_moderated,
                        'is_active' => $response->is_active,
                        'status' => $response->status,
                        'author_type' => $response->author_type,
                        'created_at' => $response->created_at,
                        'user' => [
                            'id' => $response->user->id,
                            'name' => $response->user->name,
                            'surname' => $response->user->surname,
                            'full_name' => $response->user->full_name,
                        ]
                    ];
                });
            }),

            // Kriteriya qiymətləndirmələri
            'criteria_ratings' => $this->whenLoaded('criteriaRatings', function() {
                return $this->criteriaRatings->map(function($rating) {
                    return [
                        'criteria_id' => $rating->criteria_id,
                        'criteria_name' => $rating->criteria->name ?? '',
                        'rating' => $rating->rating,
                        'rating_stars' => $rating->rating_stars,
                    ];
                });
            }),

            // Tip məlumatı
            'type' => $this->doctor_id ? 'doctor' : 'clinic',
            'type_text' => $this->doctor_id ? 'Həkim rəyi' : 'Klinika rəyi',

            // Tarix məlumatları
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'created_at_formatted' => $this->created_at?->format('d.m.Y H:i'),
            'created_at_human' => $this->created_at?->diffForHumans(),

            // Əlavə məlumatlar (şərti)
            'can_edit' => $this->when(
                $request->user()?->hasPermission('review_update'),
                true
            ),
            'can_delete' => $this->when(
                $request->user()?->hasPermission('review_delete'),
                true
            ),
            'can_moderate' => $this->when(
                $request->user()?->hasPermission('review_status') && !$this->is_moderated,
                true
            ),
            'can_verify' => $this->when(
                $request->user()?->hasPermission('review_status') && !$this->is_verified,
                true
            ),

            // Rəy rəngi (frontend üçün)
            'rating_color' => match($this->rating) {
                5 => 'success',
                4 => 'info',
                3 => 'warning',
                2, 1 => 'danger',
                default => 'secondary'
            },

            // Badge məlumatları (frontend üçün)
            'badges' => [
                'verified' => $this->is_verified,
                'moderated' => $this->is_moderated,
                'anonymous' => $this->is_anonymous,
                'has_responses' => $this->responses_count > 0,
                'reported' => $this->reports_count > 0,
            ]
        ];
    }
}
