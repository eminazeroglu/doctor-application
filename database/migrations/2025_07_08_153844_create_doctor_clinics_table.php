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
        Schema::create('doctor_clinics', function (Blueprint $table) {
            $table->id();

            // Əlaqələr
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('clinic_id')->constrained('clinics')->onDelete('cascade');

            // Əlavə məlumatlar
            $table->boolean('is_primary')->default(false); // Əsas iş yeri
            $table->json('working_hours')->nullable(); // İş saatları
            $table->json('custom_fields')->nullable(); // Əlavə məlumatlar

            $table->timestamps();

            // Eyni həkim eyni klinikada ikinci dəfə qeydiyyatdan keçə bilməz
            $table->unique(['user_id', 'clinic_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctor_clinics');
    }
};
