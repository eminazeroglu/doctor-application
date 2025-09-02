<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Həkimin təkrarlanan iş cədvəli (recurring availability)
     */
    public function up(): void
    {
        Schema::create('doctor_schedules', function (Blueprint $table) {
            $table->id();
            $table->key(); // uuid

            // Əlaqələr
            $table->foreignId('doctor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete(); // həkimin işlədiyi klinikalardan biri

            // Recurrence intervalı
            $table->date('start_date');                 // UI: Start date
            $table->date('end_date');                   // UI: End date
            $table->time('from_time');                  // UI: From
            $table->time('to_time');                    // UI: To

            // Tezlik qaydaları
            $table->enum('frequency', \App\Enums\DoctorScheduleFrequencyEnum::getValues())->default('weekly'); // UI: Frequency
            $table->unsignedSmallInteger('every')->default(1);                           // UI: Every N (weeks|days|months)

            // Həftəlik üçün seçilən günlər (0..6 ; 0=Sun)
            // UI-də Mo..Su seçimi var → JSON massivi saxlayırıq
            $table->json('days')->nullable(); // [1,2,3] kimi

            // İdarəetmə
            $table->boolean('is_active')->default(true);
            $table->text('note')->nullable();

            $table->timestamps();

            // Tez-tez sorğulanan sahələrə indekslər
            $table->index(['doctor_id', 'start_date', 'end_date']);
            $table->index(['clinic_id']);
            $table->index(['frequency', 'every']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_schedules');
    }
};
