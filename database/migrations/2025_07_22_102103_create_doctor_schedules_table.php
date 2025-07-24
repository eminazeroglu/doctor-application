<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Həkim iş cədvəli cədvəlini yaradır.
     * Bu cədvəl həkimin müxtəlif klinikalarda iş qrafikini saxlayır.
     * @return void
     */
    public function up(): void
    {
        Schema::create('doctor_schedules', function (Blueprint $table) {
            $table->id(); // Qeydin unikal ID-si
            $table->key(); // Unikal UUID
            $table->foreignId('doctor_id')->constrained()->onDelete('cascade'); // Həkim əlaqəsi
            $table->foreignId('clinic_id')->constrained()->onDelete('cascade'); // Klinika əlaqəsi
            $table->string('day_of_week'); // Həftənin günü
            $table->time('start_time'); // Başlama saatı
            $table->time('end_time'); // Bitmə saatı
            $table->boolean('is_active')->default(true); // Aktiv cədvəldir?
            $table->integer('max_appointments')->nullable(); // Maksimum randevu sayı
            $table->integer('appointment_duration')->default(30); // Randevu müddəti (dəqiqə)
            $table->text('note')->nullable(); // Əlavə qeyd
            $table->timestamps(); // Yaradılma və yenilənmə vaxtları
        });
    }

    /**
     * Həkim iş cədvəli cədvəlini silir.
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('doctor_schedules');
    }
};
