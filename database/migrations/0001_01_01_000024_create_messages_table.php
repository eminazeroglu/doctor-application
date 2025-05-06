<?php

use App\Enums\ConversationStatusEnum;
use App\Enums\ConversationTypeEnum;
use App\Enums\MessageStatusEnum;
use App\Enums\MessageTypeEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function createUserBlocked(): void
    {
        Schema::create('user_blocks', function (Blueprint $table) {
            $table->id();
            $table->key(); // UUID yaradır

            // İstifadəçi əlaqələri
            $table->foreignId('blocker_id')
                ->comment('Blok edən istifadəçi')
                ->constrained('users')
                ->onDelete('cascade');

            $table->foreignId('blocked_id')
                ->comment('Blok edilən istifadəçi')
                ->constrained('users')
                ->onDelete('cascade');

            // Əlavə məlumatlar
            $table->text('reason')->nullable()->comment('Bloklama səbəbi');
            $table->json('meta_data')->nullable()->comment('Əlavə məlumatlar');

            // Eyni istifadəçini təkrar bloklamağın qarşısını almaq üçün
            $table->unique(['blocker_id', 'blocked_id']);

            // Tarix sütunları
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function createConversation(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->key();

            // Söhbətin növü - hansı məqsədlə yaradıldığını göstərir
            $table->enum('type', ConversationTypeEnum::getValues())
                ->default(ConversationTypeEnum::LISTING); // Söhbətin növünü təyin edir: elanla bağlı, şəxsi, texniki dəstək və ya sistem bildirişi

            // Söhbətin statusu - cari vəziyyətini göstərir
            $table->enum('status', ConversationStatusEnum::getValues())
                ->default(ConversationStatusEnum::ACTIVE); // Söhbətin hazırkı statusu: aktiv, bloklanmış, arxivləşdirilmiş və ya silinmiş

            // Əlaqələr
            $table->nullableMorphs('conversationable');

            $table->foreignId('creator_id') // Söhbəti başladan istifadəçi
            ->constrained('users');

            $table->foreignId('receiver_id') // Söhbətin qarşı tərəfi
            ->constrained('users');

            /**
             * Söhbət haqqında əlavə məlumatlar
             * {
             *     "last_message": {
             *         "content": "Salam, aktivdir?",
             *         "sent_at": "2024-01-01 10:00:00"
             *     },
             *     "unread_count": 2,
             *     "listing_preview": {
             *          "title": "BMW X5 satılır",
             *          "price": "25000",
             *          "currency": "AZN",
             *          "image": "/storage/listings/123/thumb.jpg"
             *     }
             * }
             */
            $table->json('meta_data')->nullable(); // Son mesaj, oxunmayan mesaj sayı və elan haqqında qısa məlumat

            // Aktivlik göstəriciləri
            $table->timestamp('last_activity_at')
                ->nullable(); // Söhbətdəki son aktivlik tarixi - sıralama üçün istifadə olunur

            $table->boolean('is_pinned')
                ->default(false); // Söhbətin yuxarıda sabitlənib-sabitlənmədiyi

            $table->timestamp('archived_at')
                ->nullable(); // Söhbətin arxivləşdirildiyi tarix

            $table->timestamps();
            $table->softDeletes();

            // İndekslər - sorğuların optimallaşdırılması üçün
            $table->index(['creator_id', 'status', 'last_activity_at']);
            $table->index(['receiver_id', 'status', 'last_activity_at']);
            $table->index(['conversationable_id', 'type']);
            $table->index(['type', 'status']);
            $table->index('is_pinned');
        });
    }

    public function createMessage(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->key(); // UUID formatında açar

            // Mesajın aid olduğu söhbət və göndərən
            $table->foreignId('conversation_id')
                ->constrained()
                ->onDelete('cascade'); // Mesajın aid olduğu söhbətin ID-si

            $table->foreignId('sender_id')
                ->constrained('users'); // Mesajı göndərən istifadəçinin ID-si

            // Mesaj məzmunu və tipi
            $table->enum('type', MessageTypeEnum::getValues())
                ->default(MessageTypeEnum::TEXT); // Mesajın tipi: mətn, şəkil, fayl və s.

            $table->text('content')
                ->nullable(); // Mesajın mətn məzmunu

            /**
             * Mesaja əlavə edilən fayllar
             * {
             *    "files": [
             *        {
             *            "id": "f123",
             *            "type": "image",
             *            "name": "Avtomobil şəkli",
             *            "path": "/storage/messages/123/car.jpg",
             *            "size": 1048576,
             *            "mime_type": "image/jpeg",
             *            "thumbnail": "/storage/messages/123/thumbnails/car.jpg",
             *        }
             *    ]
             * }
             */
            $table->json('attachments')
                ->nullable(); // Mesaja əlavə edilən fayllar: şəkillər, sənədlər və s.

            /**
             * Əlavə mesaj məlumatları
             * {
             *    "edit_history": [
             *        {
             *            "content": "Əvvəlki mətn",
             *            "edited_at": "2024-01-01 10:00:00",
             *            "edited_by": 1
             *        }
             *    ]
             * }
             */
            $table->json('meta_data')
                ->nullable(); // Mesaj haqqında əlavə məlumatlar: redaktə tarixçəsi, reaksiyalar və s.

            // Mesajın vəziyyəti
            $table->enum('status', MessageStatusEnum::getValues())
                ->default(MessageStatusEnum::SENT); // Mesajın çatdırılma statusu: göndərilib, çatdırılıb, oxunub və s.

            $table->boolean('is_system')
                ->default(false); // Sistem tərəfindən avtomatik göndərilən mesajdırmı

            $table->boolean('is_edited')
                ->default(false); // Mesaj redaktə olunubmu

            $table->timestamp('edited_at')
                ->nullable(); // Son redaktə tarixi

            $table->timestamp('delivered_at')
                ->nullable(); // Mesajın qarşı tərəfə çatdırıldığı tarix

            $table->timestamp('read_at')
                ->nullable(); // Mesajın oxunduğu tarix

            // Sistem sütunları
            $table->timestamps();
            $table->softDeletes();

            // İndekslər
            $table->index(['conversation_id', 'created_at']);
            $table->index(['sender_id', 'created_at']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->createConversation();
        $this->createMessage();
        $this->createUserBlocked();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_blocks');
        Schema::dropIfExists('conversations');
        Schema::dropIfExists('messages');
    }
};
