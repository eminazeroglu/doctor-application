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
            $table->id();
            $table->key(); // uuid

            // Əlaqələr
            $table->foreignId('doctor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('clinic_id')->nullable()->constrained()->nullOnDelete();

            // Interval
            $table->dateTime('start_time'); // əvvəlki start_datetime əvəzinə
            $table->dateTime('end_time');   // əvvəlki end_datetime əvəzinə

            // Qeyd
            $table->string('note')->nullable(); // reason/description yerinə tək sahə

            $table->timestamps();

            // Sorğular üçün indekslər
            $table->index(['doctor_id', 'start_time', 'end_time']);
            $table->index(['clinic_id']);
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
