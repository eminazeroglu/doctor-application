<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Həkim təhsil məlumatları cədvəlini yaradır.
     * Bu cədvəl həkimlərin təhsil tarixçəsini saxlayır.
     * @return void
     */
    public function up(): void
    {
        Schema::create('doctor_education', function (Blueprint $table) {
            $table->id(); // Qeydin unikal ID-si
            $table->key(); // Unikal UUID
            $table->foreignId('doctor_id')->constrained()->onDelete('cascade'); // Həkim əlaqəsi
            $table->string('university'); // Təhsil müəssisəsinin adı
            $table->string('faculty')->nullable(); // Fakültə
            $table->string('degree'); // Elmi dərəcə
            $table->string('specialization')->nullable(); // İxtisas
            $table->date('start_date'); // Başlama tarixi
            $table->date('end_date')->nullable(); // Bitirmə tarixi
            $table->string('location')->nullable(); // Yer
            $table->text('description')->nullable(); // Əlavə təsvir
            $table->boolean('is_currently_studying')->default(false); // Hal-hazırda təhsil alır?
            $table->string('document_path')->nullable(); // Sənəd faylının yolu
            $table->timestamps(); // Yaradılma və yenilənmə vaxtları
        });
    }

    /**
     * Həkim təhsil məlumatları cədvəlini silir.
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('doctor_education');
    }
};
