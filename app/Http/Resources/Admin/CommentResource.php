<?php

namespace App\Http\Resources\Admin;

use App\Enums\CommentReactionType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
{
    /**
     * Comment modelini API response-a çevir.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'content' => $this->content,
            'commentable' => $this->whenLoaded('commentable'),
            'formatted_content' => $this->resource->formatted_content,
            'is_private' => $this->is_private,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at instanceof \Carbon\Carbon
                ? $this->created_at->toIso8601String()
                : (string)$this->created_at,
            'updated_at' => $this->updated_at instanceof \Carbon\Carbon
                ? $this->updated_at->toIso8601String()
                : (string)$this->updated_at,
            'created_at_human' => $this->created_at instanceof \Carbon\Carbon
                ? $this->created_at->diffForHumans()
                : null,
            'is_edited' => $this->resource->isEdited(),

            // Əlaqələr
            'author' => new ReferenceResource($this->whenLoaded('author')),
            'parent_id' => $this->parent_id,
            'parent' => new ReferenceResource($this->whenLoaded('parent')),
            'replies_count' => $this->whenCounted('replies'),
            'replies' => CommentResource::collection($this->whenLoaded('replies')),

            // Meta data
            'reactions' => $this->getReactionsSummary(),
            'has_attachments' => !empty($this->resource->getMetaData('attachments', [])),
            'attachments' => $this->resource->getMetaData('attachments', []),

            // Yetkiler və əməliyyatlar
            'can_edit' => $this->resource->canEdit,
            'can_reply' => auth()->check(),
            'can_report' => $this->canReport(),

            // Polymorphic əlaqə tipi
            'commentable_type_text' => $this->commentable_type_text,
            'commentable_type' => $this->getCommentableType(),
            'commentable_id' => $this->commentable_id,
        ];
    }

    /**
     * Reaksiyaların strukturlaşdırılmış xülasəsini əldə edir
     *
     * @return array
     */
    protected function getReactionsSummary(): array
    {
        $reactions = $this->getMetaData('reactions', []);
        $summary = [
            'total' => 0,
            'types' => [],
            'current_user' => []
        ];

        // Reaksiya tipinə görə sayları hesablayırıq
        foreach ($reactions as $type => $users) {
            $count = count($users);
            $summary['types'][$type] = [
                'name' => CommentReactionType::getDescription($type),
                'count' => $count,
            ];
            $summary['total'] += $count;

            // Cari istifadəçinin reaksiyalarını əlavə edirik
            if (auth()->check() && in_array(auth()->id(), $users)) {
                $summary['current_user'][] = $type;
            }
        }

        return $summary;
    }

    /**
     * Şərhin spam olaraq bildirə bilməsi üçün yoxlama
     *
     * @return bool
     */
    protected function canReport(): bool
    {
        if (!auth()->check()) {
            return false;
        }

        $userId = auth()->id();
        $spamReports = $this->getMetaData('spam_reports', []);

        // İstifadəçi artıq spam bildiriş edibsə, yenidən edə bilməz
        foreach ($spamReports as $report) {
            if ($report['reported_by'] === $userId) {
                return false;
            }
        }

        // İstifadəçi öz şərhini spam olaraq bildirə bilməz
        return $this->user_id !== $userId;
    }

    /**
     * Comentable tipinin insan tərəfindən oxuna bilən formasını qaytarır
     *
     * @return string
     */
    protected function getCommentableType(): string
    {
        $typeMap = [
            'App\\Models\\Listing' => 'listing',
            'App\\Models\\Company' => 'company',
            'App\\Models\\User' => 'user'
        ];

        return $typeMap[$this->commentable_type] ?? 'unknown';
    }
}
