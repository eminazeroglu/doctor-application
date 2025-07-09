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
        Schema::create('doctor_experience', function (Blueprint $table) {
            $table->id();
            $table->key(); // UUID yaradır

            // Əlaqələr
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // İş təcrübəsi məlumatları
            $table->string('institution'); // İş yeri/Klinika
            $table->string('position'); // Vəzifə
            $table->string('location')->nullable(); // Şəhər/Ölkə
            $table->date('start_date'); // Başlama tarixi
            $table->date('end_date')->nullable(); // Bitirmə tarixi
            $table->boolean('is_current')->default(false); // Hal-hazırda davam edirmi
            $table->text('description')->nullable(); // İş haqqında əlavə məlumat
            $table->json('custom_fields')->nullable(); // Əlavə məlumatlar

            // İş ardıcıllığı
            $table->integer('order')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctor_experience');
    }
};
