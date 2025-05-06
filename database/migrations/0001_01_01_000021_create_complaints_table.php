<?php

use App\Enums\ComplaintMessageStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\ComplaintStatusEnum;

return new class extends Migration
{
    public function createComplaints(): void
    {
        Schema::create('complaints', function (Blueprint $table) {
            // Əsas ID sütunu
            $table->id();

            // UUID və kod yaradırıq (BlueprintProvider-dən gələn makrolar)
            $table->key();    // uuid sütununu yaradır
            $table->code();   // unique kod sütunu yaradır (CPL-12345)

            // Şikayəti yaradan istifadəçi
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');

            // Şikayət edilən obyekt (user, company və ya listing)
            $table->morphs('complaintable');

            // Şikayətin məzmunu
            $table->string('title');
            $table->text('description');
            $table->json('attachments')->nullable();

            // Status və həll məlumatları
            $table->string('status')->default(ComplaintStatusEnum::Pending);
            $table->text('resolution_note')->nullable();
            $table->timestamp('resolved_at')->nullable();

            // Həll edən moderator
            $table->foreignId('resolved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Tracking və audit məlumatları (BlueprintProvider-dən)
            $table->trackable();    // created_by, updated_by sütunlarını əlavə edir

            // System timestamps
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function createComplaintMessages(): void
    {
        Schema::create('complaint_messages', function (Blueprint $table) {
            $table->id();
            $table->key();   // uuid sütunu

            // Hansı şikayətə aid olduğu
            // cascade: şikayət silindikdə mesajlar da silinəcək
            $table->foreignId('complaint_id')
                ->constrained('complaints')
                ->onDelete('cascade');

            // Mesajı yazan istifadəçi
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('restrict');  // istifadəçi silinə bilməz mesajları varsa

            // Mesajın məzmunu
            $table->text('message');
            $table->json('attachments')->nullable();  // əlavə edilən fayllar

            // Mesajın statusu və növü
            $table->string('status')->default(ComplaintMessageStatusEnum::Pending);
            $table->boolean('is_staff_reply')->default(false);  // admin/staff tərəfindən yazılıbsa

            // Tracking və audit
            $table->timestamps();
            $table->softDeletes();

            // İndekslər - sürətli axtarış üçün
            $table->index(['complaint_id', 'created_at']);
            $table->index(['status', 'created_at']);
        });
    }

    public function up(): void
    {
        $this->createComplaints();
        $this->createComplaintMessages();
    }

    public function down(): void
    {
        Schema::dropIfExists('complaints');
        Schema::dropIfExists('complaint_messages');
    }
};
