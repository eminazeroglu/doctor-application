<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rəylər cədvəlini yaradır.
     * Bu cədvəl istifadəçilərin həkim və klinikalar haqqında rəylərini saxlayır.
     * @return void
     */
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id(); // Rəyin unikal ID-si
            $table->key(); // Unikal UUID
            $table->foreignId('patient_id')->constrained()->onDelete('cascade'); // Xəstə əlaqəsi
            $table->foreignId('doctor_id')->nullable()->constrained()->nullOnDelete(); // Həkim əlaqəsi
            $table->foreignId('clinic_id')->nullable()->constrained()->nullOnDelete(); // Klinika əlaqəsi
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete(); // Randevu əlaqəsi
            $table->text('comment')->nullable(); // Rəy mətni
            $table->integer('rating'); // Qiymətləndirmə (1-5 arası)
            $table->boolean('is_anonymous')->default(false); // Anonim rəydir?
            $table->boolean('is_verified')->default(false); // Rəy təsdiqlənib?
            $table->boolean('is_moderated')->default(false); // Rəy moderasiyadan keçib?
            $table->boolean('is_active')->default(true); // Rəy aktivdir?
            $table->timestamps(); // Yaradılma və yenilənmə vaxtları
        });
    }

    /**
     * Rəylər cədvəlini silir.
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
