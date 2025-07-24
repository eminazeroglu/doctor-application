<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Klinika xidmətləri cədvəlini yaradır.
     * Bu cədvəl klinikada təklif olunan xidmətləri və qiymətləri saxlayır.
     * @return void
     */
    public function up(): void
    {
        Schema::create('clinic_services', function (Blueprint $table) {
            $table->id(); // Qeydin unikal ID-si
            $table->foreignId('clinic_id')->constrained()->onDelete('cascade'); // Klinika əlaqəsi
            $table->foreignId('service_id')->constrained()->onDelete('cascade'); // Xidmət əlaqəsi
            $table->decimal('price', 10, 2)->nullable(); // Xidmət qiyməti
            $table->integer('duration')->nullable(); // Xidmət müddəti (dəqiqə)
            $table->text('description')->nullable(); // Əlavə təsvir
            $table->boolean('is_active')->default(true); // Aktivdir?
            $table->timestamps(); // Yaradılma və yenilənmə vaxtları

            // Eyni klinika üçün xidmətin unikallığı
            $table->unique(['clinic_id', 'service_id']);
        });
    }

    /**
     * Klinika xidmətləri cədvəlini silir.
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('clinic_services');
    }
};
