<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Həkim məşğulluq cədvəlini yaradır.
     * Bu cədvəl həkimin məşğul olduğu və ya işdə olmadığı günləri saxlayır.
     * @return void
     */
    public function up(): void
    {
        Schema::create('doctor_unavailability', function (Blueprint $table) {
            $table->id(); // Qeydin unikal ID-si
            $table->key(); // Unikal UUID
            $table->foreignId('doctor_id')->constrained()->onDelete('cascade'); // Həkim əlaqəsi
            $table->foreignId('clinic_id')->nullable()->constrained()->nullOnDelete(); // Klinika əlaqəsi (NULL - bütün klinikalar üçün)
            $table->dateTime('start_datetime'); // Başlama tarixi və saatı
            $table->dateTime('end_datetime'); // Bitmə tarixi və saatı
            $table->string('reason')->nullable(); // Məşğulluq səbəbi
            $table->text('description')->nullable(); // Əlavə təsvir
            $table->boolean('is_recurring')->default(false); // Təkrarlanan məşğulluqdur?
            $table->string('recurring_pattern')->nullable(); // Təkrarlanma qaydası
            $table->timestamps(); // Yaradılma və yenilənmə vaxtları
        });
    }

    /**
     * Həkim məşğulluq cədvəlini silir.
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('doctor_unavailability');
    }
};
