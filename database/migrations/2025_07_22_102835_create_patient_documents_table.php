<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Xəstə sənədləri cədvəlini yaradır.
     * Bu cədvəl xəstələrin tibbi sənədlərini saxlayır.
     * @return void
     */
    public function up(): void
    {
        Schema::create('patient_documents', function (Blueprint $table) {
            $table->id(); // Sənədin unikal ID-si
            $table->key(); // Unikal UUID
            $table->foreignId('patient_id')->constrained()->onDelete('cascade'); // Xəstə əlaqəsi
            $table->foreignId('doctor_id')->nullable()->constrained()->nullOnDelete(); // Həkim əlaqəsi
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete(); // Randevu əlaqəsi
            $table->string('document_type'); // Sənəd növü (lab_result, x_ray, prescription, etc.)
            $table->string('title'); // Sənəd başlığı
            $table->text('description')->nullable(); // Sənəd təsviri
            $table->date('document_date'); // Sənəd tarixi
            $table->string('file_path'); // Fayl yolu
            $table->string('file_type'); // Fayl növü (MIME type)
            $table->integer('file_size'); // Fayl ölçüsü (bayt ilə)
            $table->boolean('is_verified')->default(false); // Sənəd təsdiqlənib?
            $table->boolean('is_private')->default(false); // Sənəd xüsusidir?
            $table->timestamps(); // Yaradılma və yenilənmə vaxtları
        });
    }

    /**
     * Xəstə sənədləri cədvəlini silir.
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_documents');
    }
};
