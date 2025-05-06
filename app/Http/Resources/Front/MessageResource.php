<?php

namespace App\Http\Resources\Front;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Mesajı göndərən istifadəçi məlumatlarını əldə edirik
        $sender = $this->resource->sender;

        // Mesajın cari istifadəçi tərəfindən göndərilib-göndərilmədiyini təyin edirik
        // Bu, mesajları sol/sağ tərəfdə göstərmək üçün istifadə olunur
        $isFromCurrentUser = $this->resource->sender_id === Auth::id();

        // Formatlanmış mesaj məlumatlarını qaytarırıq
        return [
            'id' => $this->resource->id,               // Mesajın ID-si
            'uuid' => $this->resource->uuid,           // Mesajın unikal UUID-si
            'conversation_id' => $this->resource->conversation_id,  // Aid olduğu söhbətin ID-si
            'sender' => [                              // Mesajı göndərən haqqında məlumat bloku
                'id' => $sender->id,                   // Göndərənin ID-si
                'name' => $sender->name,               // Göndərənin adı
                'surname' => $sender->surname,         // Göndərənin soyadı
                'fullname' => $sender->name . ' ' . $sender->surname,  // Tam adı
                'username' => $sender->username,       // İstifadəçi adı
                'photo' => $sender->photo,             // Profil şəkli
            ],
            'type' => $this->resource->type,           // Mesajın növü (mətn, şəkil, fayl və s.)
            'content' => $this->resource->content,     // Mesajın məzmunu
            'attachments' => $this->resource->attachments,  // Məsaja əlavə edilən fayllar
            'status' => $this->resource->status,       // Mesajın statusu (göndərildi, çatdırıldı, oxundu və s.)
            'is_edited' => (bool)$this->resource->is_edited,  // Mesajın redaktə edilib-edilmədiyi
            'is_system' => (bool)$this->resource->is_system,  // Sistem mesajıdırmı
            'is_from_current_user' => $isFromCurrentUser,  // Cari istifadəçi tərəfindən göndərilibmi
            'created_at' => $this->resource->created_at,    // Mesajın göndərilmə tarixi (tam)
            'formatted_created_at' => $this->resource->created_at,  // Formatlanmış göndərilmə tarixi
            'edited_at' => $this->resource->edited_at,      // Redaktə edilmə tarixi
            'delivered_at' => $this->resource->delivered_at,  // Çatdırılma tarixi
            'read_at' => $this->resource->read_at,          // Oxunma tarixi
            'meta_data' => $this->resource->meta_data,      // Əlavə məlumatlar (JSON formatında)
            // Cari istifadəçi üçün silinib-silinmədiyini yoxlayırıq
            'is_deleted_for_current_user' => $this->isDeletedForCurrentUser(),  // Mesaj silinibmi
        ];
    }

    /**
     * Mesajın cari istifadəçi üçün silinib-silinmədiyini yoxlayır
     * Bu metod mesajın meta_data sahəsindəki deleted_for massivində
     * cari istifadəçinin ID-si olub-olmadığını yoxlayır
     */
    protected function isDeletedForCurrentUser(): bool
    {
        $metaData = $this->resource->meta_data ?? [];  // Meta məlumatları əldə edirik
        $deletedFor = $metaData['deleted_for'] ?? [];  // Kimlər üçün silindiyini əldə edirik

        // Cari istifadəçi üçün silinibmi yoxlayırıq
        return in_array(Auth::id(), $deletedFor);
    }
}
