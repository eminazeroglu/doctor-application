<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Xəstə tibbi qeydləri cədvəlini yaradır.
     * Bu cədvəl xəstələrin tibbi qeydlərini saxlayır.
     * @return void
     */
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::create('patient_medical_records', function (Blueprint $table) {
            $table->id(); // Qeydin unikal ID-si
            $table->key(); // Unikal UUID
            $table->foreignId('patient_id')->constrained()->onDelete('cascade'); // Xəstə əlaqəsi
            $table->foreignId('doctor_id')->nullable()->constrained()->nullOnDelete(); // Həkim əlaqəsi
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete(); // Randevu əlaqəsi
            $table->string('record_type'); // Qeyd növü (diagnosis, checkup, test_result, etc.)
            $table->string('diagnosis')->nullable(); // Diaqnoz
            $table->text('description')->nullable(); // Ətraflı təsvir
            $table->text('treatment')->nullable(); // Müalicə
            $table->text('prescription')->nullable(); // Resept
            $table->text('notes')->nullable(); // Əlavə qeydlər
            $table->date('record_date'); // Qeyd tarixi
            $table->json('additional_info')->nullable(); // Əlavə məlumatlar
            $table->string('document_path')->nullable(); // Sənəd faylının yolu
            $table->boolean('is_private')->default(false); // Qeyd xüsusidir?
            $table->timestamps(); // Yaradılma və yenilənmə vaxtları
        });
        Schema::enableForeignKeyConstraints();
    }

    /**
     * Xəstə tibbi qeydləri cədvəlini silir.
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_medical_records');
    }
};
