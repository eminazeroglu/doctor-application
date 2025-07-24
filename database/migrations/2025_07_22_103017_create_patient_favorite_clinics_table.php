<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Xəstə favori klinikalar cədvəlini yaradır.
     * Bu cədvəl xəstələrin favori klinikalarını saxlayır.
     * @return void
     */
    public function up(): void
    {
        Schema::create('patient_favorite_clinics', function (Blueprint $table) {
            $table->id(); // Qeydin unikal ID-si
            $table->foreignId('patient_id')->constrained()->onDelete('cascade'); // Xəstə əlaqəsi
            $table->foreignId('clinic_id')->constrained()->onDelete('cascade'); // Klinika əlaqəsi
            $table->text('note')->nullable(); // Qeyd
            $table->timestamps(); // Yaradılma və yenilənmə vaxtları

            // Eyni xəstə-klinika cütlüyünün unikallığı
            $table->unique(['patient_id', 'clinic_id']);
        });
    }

    /**
     * Xəstə favori klinikalar cədvəlini silir.
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_favorite_clinics');
    }
};
