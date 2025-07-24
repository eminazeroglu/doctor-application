<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Həkim klinika xidmətləri cədvəlini yaradır.
     * Bu cədvəl həkimin hər bir klinikada təklif etdiyi xidmətləri saxlayır.
     * @return void
     */
    public function up(): void
    {
        Schema::create('doctor_clinic_services', function (Blueprint $table) {
            $table->id(); // Qeydin unikal ID-si
            $table->foreignId('doctor_id')->constrained()->onDelete('cascade'); // Həkim əlaqəsi
            $table->foreignId('clinic_id')->constrained()->onDelete('cascade'); // Klinika əlaqəsi
            $table->foreignId('service_id')->constrained()->onDelete('cascade'); // Xidmət əlaqəsi
            $table->decimal('price', 10, 2)->nullable(); // Xidmət qiyməti
            $table->integer('duration')->nullable(); // Xidmət müddəti (dəqiqə)
            $table->text('description')->nullable(); // Əlavə təsvir
            $table->boolean('is_active')->default(true); // Aktiv xidmətdir?
            $table->timestamps(); // Yaradılma və yenilənmə vaxtları

            // Eyni həkim-klinika-xidmət üçlüyünün unikallığı
            $table->unique(['doctor_id', 'clinic_id', 'service_id']);
        });
    }

    /**
     * Həkim klinika xidmətləri cədvəlini silir.
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('doctor_clinic_services');
    }
};
