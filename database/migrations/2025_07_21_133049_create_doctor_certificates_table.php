<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Həkim sertifikatları cədvəlini yaradır.
     * Bu cədvəl həkimlərin sertifikat və lisenziyalarını saxlayır.
     * @return void
     */
    public function up(): void
    {
        Schema::create('doctor_certificates', function (Blueprint $table) {
            $table->id(); // Qeydin unikal ID-si
            $table->key(); // Unikal UUID
            $table->foreignId('doctor_id')->constrained()->onDelete('cascade'); // Həkim əlaqəsi
            $table->string('name'); // Sertifikatın adı
            $table->string('issuing_organization'); // Verən təşkilat
            $table->date('issue_date'); // Verilmə tarixi
            $table->date('expiry_date')->nullable(); // Bitmə tarixi
            $table->text('description')->nullable(); // Əlavə təsvir
            $table->string('document_path')->nullable(); // Sənəd faylının yolu
            $table->boolean('is_verified')->default(false); // Sertifikat təsdiqlənib?
            $table->timestamps(); // Yaradılma və yenilənmə vaxtları
        });
    }

    /**
     * Həkim sertifikatları cədvəlini silir.
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('doctor_certificates');
    }
};
