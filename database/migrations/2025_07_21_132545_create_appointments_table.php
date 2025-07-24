<?php

use App\Enums\AppointmentStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Randevular cədvəlini yaradır.
     * Bu cədvəl randevu məlumatlarını saxlayır.
     * @return void
     */
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id(); // Randevunun unikal ID-si
            $table->key(); // Unikal UUID
            $table->foreignId('doctor_id')->constrained()->onDelete('cascade'); // Həkim əlaqəsi
            $table->foreignId('patient_id')->constrained()->onDelete('cascade'); // Xəstə əlaqəsi
            $table->foreignId('clinic_id')->nullable()->constrained()->nullOnDelete(); // Klinika əlaqəsi
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete(); // Xidmət əlaqəsi
            $table->string('appointment_status')->default(AppointmentStatusEnum::Pending); // Status əlaqəsi
            $table->dateTime('start_time'); // Başlama vaxtı
            $table->dateTime('end_time'); // Bitmə vaxtı
            $table->text('complaint')->nullable(); // Şikayət
            $table->text('notes')->nullable(); // Əlavə qeydlər
            $table->decimal('price', 10, 2)->nullable(); // Qiymət
            $table->boolean('is_paid')->default(false); // Ödənilib?
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete(); // Ödəniş əlaqəsi
            $table->text('cancel_reason')->nullable(); // Ləğv səbəbi
            $table->string('location')->nullable(); // Yer (klinikada, onlayn, ev ziyarəti)
            $table->string('consultation_type')->default('in_person'); // Konsultasiya növü (in_person, online, home_visit)
            $table->json('additional_info')->nullable(); // Əlavə məlumatlar
            $table->timestamps(); // Yaradılma və yenilənmə vaxtları
            $table->softDeletes(); // Yumşaq silmə (soft delete) üçün
        });
    }

    /**
     * Randevular cədvəlini silir.
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
