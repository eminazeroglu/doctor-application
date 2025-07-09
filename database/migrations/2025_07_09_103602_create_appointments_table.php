<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->key(); // UUID yaradır
            $table->string('appointment_number')->unique(); // Randevu nömrəsi (APT-20230101-001)

            // Əlaqələr
            $table->foreignId('doctor_id')->constrained('users')->onDelete('cascade'); // Həkim
            $table->foreignId('patient_id')->constrained('users')->onDelete('cascade'); // Pasient
            $table->foreignId('service_id')->nullable()->constrained('services')->onDelete('set null'); // Xidmət
            $table->foreignId('clinic_id')->constrained()->onDelete('cascade'); // Klinika
            $table->foreignId('availability_id')->nullable()->constrained('doctor_availability')->onDelete('set null'); // Boş vaxt

            // Randevu məlumatları
            $table->string('status')->nullable(); // Status
            $table->date('appointment_date'); // Randevu tarixi
            $table->time('start_time'); // Başlama saatı
            $table->time('end_time'); // Bitmə saatı
            $table->text('reason')->nullable(); // Randevunun səbəbi
            $table->text('symptoms')->nullable(); // Simptomlar
            $table->text('notes')->nullable(); // Əlavə qeydlər
            $table->text('diagnosis')->nullable(); // Diaqnoz
            $table->text('treatment')->nullable(); // Müalicə
            $table->text('prescription')->nullable(); // Resept
            $table->text('doctor_notes')->nullable(); // Həkimin qeydləri

            // Maliyyə məlumatları
            $table->decimal('fee', 10, 2)->default(0); // Qiymət
            $table->boolean('is_paid')->default(false); // Ödənilib?
            $table->timestamp('paid_at')->nullable(); // Ödəniş tarixi
            $table->string('payment_method')->nullable(); // Ödəniş metodu
            $table->string('payment_reference')->nullable(); // Ödəniş referansı

            // Status tarixləri
            $table->timestamp('confirmed_at')->nullable(); // Təsdiqlənmə tarixi
            $table->timestamp('cancelled_at')->nullable(); // Ləğv edilmə tarixi
            $table->string('cancellation_reason')->nullable(); // Ləğv etmə səbəbi
            $table->timestamp('completed_at')->nullable(); // Tamamlanma tarixi

            // Sistem məlumatları
            $table->json('meta_data')->nullable(); // Əlavə məlumatlar
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); // Yaradan istifadəçi

            $table->timestamps();
            $table->softDeletes();

            // İndekslər
            $table->index(['doctor_id', 'patient_id', 'clinic_id']);
            $table->index(['appointment_date', 'start_time', 'end_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
