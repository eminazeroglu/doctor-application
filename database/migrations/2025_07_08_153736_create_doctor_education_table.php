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
        Schema::create('doctor_education', function (Blueprint $table) {
            $table->id();
            $table->key(); // UUID yaradır

            // Əlaqələr
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Təhsil məlumatları
            $table->string('institution'); // Universitet/Ali məktəb
            $table->string('degree'); // Dərəcə (bakalavr, magistr, PhD və s.)
            $table->string('field_of_study')->nullable(); // İxtisas
            $table->string('location')->nullable(); // Şəhər/Ölkə
            $table->date('start_date'); // Başlama tarixi
            $table->date('end_date')->nullable(); // Bitirmə tarixi
            $table->boolean('is_current')->default(false); // Hal-hazırda davam edirmi
            $table->text('description')->nullable(); // Əlavə məlumat
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
        Schema::dropIfExists('doctor_education');
    }
};
