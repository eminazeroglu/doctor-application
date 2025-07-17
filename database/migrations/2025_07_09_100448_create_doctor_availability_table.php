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
        Schema::create('doctor_availability', function (Blueprint $table) {
            $table->id();
            $table->key(); // UUID yaradır

            // Əlaqələr
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Həkim
            $table->foreignId('clinic_id')->constrained()->onDelete('cascade'); // Klinika
            $table->foreignId('schedule_id')->nullable()->constrained('doctor_schedules')->onDelete('set null'); // Yaradıldığı cədvəl

            // Boş vaxt məlumatları
            $table->date('date'); // Tarix
            $table->time('start_time'); // Başlama saatı
            $table->time('end_time'); // Bitmə saatı
            $table->enum('status', ['available', 'busy', 'unavailable'])->default('available'); // Status
            $table->string('reason')->nullable(); // Status səbəbi (məsələn, niyə məşğuldur)
            $table->json('meta_data')->nullable(); // Əlavə məlumatlar

            $table->timestamps();

            // İndekslər
            $table->index(['user_id', 'clinic_id', 'date', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctor_availability');
    }
};
