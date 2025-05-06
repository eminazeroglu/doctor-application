<?php

namespace App\Http\Resources\Front;

use App\Enums\CommentReactionType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
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
            'content' => $this->formatted_content,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // İstifadəçi məlumatları
            'author' => $this->when($this->whenLoaded('author'), function () {
                return [
                    'uuid' => $this->author->uuid,
                    'fullname' => $this->author->fullname,
                    'photo' => $this->author->photo,
                    'username' => $this->author->username,
                ];
            }),

            // Reaksiyalar və statistika
            'reactions' => $this->getReactionsSummary(),
            'attachments' => $this->getAttachments(),

            // Parent/Reply struktur məlumatları
            'parent' => $this->when($this->parent_id, function() {
                return [
                    'id' => $this->parent->id,
                    'uuid' => $this->parent->uuid,
                    'content' => $this->parent->content,
                    'author' => [
                        'id' => $this->parent->author->id,
                        'fullname' => $this->parent->author->fullname,
                        'photo' => $this->parent->author->photo,
                    ],
                ];
            }),

            // Cavabların özü və sayı
            'replies_count' => $this->whenLoaded('replies', function() {
                return $this->replies->count();
            }, 0),
            'replies' => CommentResource::collection($this->whenLoaded('replies')),

            // Spam hesabat məlumatları (admin istifadəçilər üçün)
            'spam_reports' => $this->when(
                auth()->check() && auth()->user()->hasRole('admin'),
                fn() => $this->getMetaData('spam_reports', [])
            ),
        ];
    }

    protected function getReactionsSummary(): array
    {
        $reactions = $this->getMetaData('reactions', []);
        $summary = [
            'total' => 0,
            'types' => [],
            'current_user' => []
        ];

        foreach ($reactions as $type => $users) {
            $count = count($users);
            $summary['types'][$type] = [
                'name' => CommentReactionType::getDescription($type),
                'count' => $count,
            ];
            $summary['total'] += $count;
        }

        return $summary;
    }
}
