<?php

namespace App\Http\Resources\Front;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class ConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Söhbətin qarşı tərəfini təyin edirik
        // Əgər cari istifadəçi söhbəti yaradansa, qarşı tərəf alıcıdır, əks halda yaradıcıdır
        $participant = $this->resource->creator_id === Auth::id() ? $this->resource->receiver : $this->resource->creator;

        // Cari istifadəçi üçün oxunmamış mesaj sayını əldə edirik
        // Bu, söhbət listində göstərilmək üçün istifadə olunur
        $unreadCount = $this->resource->getUnreadCount(Auth::id());

        $isBlocked = Auth::user()->hasBlocked($participant->id);
        $isBlockedBy = Auth::user()->isBlockedBy($participant->id);

        // Formatlanmış söhbət məlumatlarını qaytarırıq
        return [
            'id' => $this->resource->id,                 // Söhbətin ID-si
            'uuid' => $this->resource->uuid,             // Söhbətin unikal UUID-si (frontend-də istifadə üçün)
            'type' => $this->resource->type,             // Söhbətin növü (şəxsi, elan, dəstək, və s.)
            'status' => $this->resource->status,         // Söhbətin statusu (aktiv, bloklanmış, və s.)
            'is_pinned' => (bool)$this->resource->is_pinned,  // Söhbətin sabitlənmiş olub-olmadığı
            'participant' => [                           // Qarşı tərəf haqqında məlumat bloku
                'id' => $participant->id,                // Qarşı tərəfin ID-si
                'name' => $participant->name,            // Qarşı tərəfin adı
                'surname' => $participant->surname,      // Qarşı tərəfin soyadı
                'fullname' => $participant->name . ' ' . $participant->surname,  // Tam adı
                'username' => $participant->username,    // İstifadəçi adı
                'photo' => $participant->photo,          // Profil şəkli
            ],
            'last_message' => $this->resource->lastMessage ? [
                'id' => $this->resource->lastMessage->id,
                'uuid' => $this->resource->lastMessage->uuid,
                'content' => $this->resource->lastMessage->content,
                'created_at' => $this->resource->lastMessage->created_at,
                'status' => $this->resource->lastMessage->status,
                'status_text' => $this->resource->lastMessage->status_text,
            ] : null,  // Son mesaj məlumatları
            'last_activity_at' => $this->resource->last_activity_at,  // Son aktivlik vaxtı (söhbətləri sıralamaq üçün)
            'created_at' => $this->resource->created_at,  // Söhbətin yaradılma tarixi
            'unread_count' => $unreadCount,               // Oxunmamış mesaj sayı
            'messages' => MessageResource::collection($this->whenLoaded('messages')),  // Söhbətin mesajları (əgər əlaqə yüklənmişdirsə)
            'meta_data' => $this->resource->meta_data,    // Əlavə məlumatlar (JSON formatında)
            'conversationable_type' => $this->resource->conversationable_type,  // Söhbətin aid olduğu model növü
            'conversationable_id' => $this->resource->conversationable_id,      // Söhbətin aid olduğu model ID-si
            'archived_at' => $this->resource->archived_at,  // Söhbətin arxivləşdirilmə tarixi,
            'is_blocked' => $isBlocked,
            'is_blocked_by' => $isBlockedBy
        ];
    }
}
