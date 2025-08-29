<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Həkim-klinika əlaqə cədvəlini yaradır.
     * Bu cədvəl həkimin hansı klinikalarda işlədiyini göstərir.
     * @return void
     */
    public function up(): void
    {
        Schema::create('doctor_clinic', function (Blueprint $table) {
            $table->id(); // Qeydin unikal ID-si
            $table->foreignId('doctor_id')->constrained()->onDelete('cascade'); // Həkim əlaqəsi
            $table->foreignId('clinic_id')->constrained()->onDelete('cascade'); // Klinika əlaqəsi
            $table->json('start_date')->nullable();
            $table->boolean('is_main_workplace')->default(false); // Əsas iş yeridir?
            $table->boolean('is_active')->default(true); // Aktiv əlaqədir?
            $table->text('note')->nullable(); // Əlavə qeyd
            $table->timestamps(); // Yaradılma və yenilənmə vaxtları

            // Eyni həkim-klinika cütlüyünün unikallığı
            $table->unique(['doctor_id', 'clinic_id']);
        });
    }

    /**
     * Həkim-klinika əlaqə cədvəlini silir.
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('doctor_clinic');
    }
};
