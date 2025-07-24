<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Təkrarlanan randevular cədvəlini yaradır.
     * Bu cədvəl təkrarlanan randevuları saxlayır.
     * @return void
     */
    public function up(): void
    {
        Schema::create('appointment_recurring', function (Blueprint $table) {
            $table->id(); // Təkrarlanan randevunun unikal ID-si
            $table->uuid('uuid')->unique(); // Unikal UUID
            $table->foreignId('doctor_id')->constrained()->onDelete('cascade'); // Həkim əlaqəsi
            $table->foreignId('patient_id')->constrained()->onDelete('cascade'); // Xəstə əlaqəsi
            $table->foreignId('clinic_id')->constrained()->onDelete('cascade'); // Klinika əlaqəsi
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete(); // Xidmət əlaqəsi
            $table->string('frequency'); // Tezlik (daily, weekly, monthly)
            $table->integer('interval')->default(1); // İnterval
            $table->json('days_of_week')->nullable(); // Həftənin günləri
            $table->date('start_date'); // Başlama tarixi
            $table->date('end_date')->nullable(); // Bitmə tarixi
            $table->time('start_time'); // Başlama saatı
            $table->time('end_time'); // Bitmə saatı
            $table->text('notes')->nullable(); // Əlavə qeydlər
            $table->boolean('is_active')->default(true); // Aktivdir?
            $table->timestamps(); // Yaradılma və yenilənmə vaxtları
        });
    }

    /**
     * Təkrarlanan randevular cədvəlini silir.
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('appointment_recurring');
    }
};
