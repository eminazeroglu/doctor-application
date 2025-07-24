<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Həkim iş təcrübəsi cədvəlini yaradır.
     * Bu cədvəl həkimlərin iş təcrübəsi tarixçəsini saxlayır.
     * @return void
     */
    public function up(): void
    {
        Schema::create('doctor_experience', function (Blueprint $table) {
            $table->id(); // Qeydin unikal ID-si
            $table->key(); // Unikal UUID
            $table->foreignId('doctor_id')->constrained()->onDelete('cascade'); // Həkim əlaqəsi
            $table->string('workplace'); // İş yerinin adı
            $table->string('position'); // Vəzifə
            $table->date('start_date'); // Başlama tarixi
            $table->date('end_date')->nullable(); // Bitirmə tarixi
            $table->string('location')->nullable(); // Yer
            $table->text('description')->nullable(); // Əlavə təsvir
            $table->boolean('is_current_job')->default(false); // Hal-hazırda burada işləyir?
            $table->string('document_path')->nullable(); // Sənəd faylının yolu
            $table->timestamps(); // Yaradılma və yenilənmə vaxtları
        });
    }

    /**
     * Həkim iş təcrübəsi cədvəlini silir.
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('doctor_experience');
    }
};
