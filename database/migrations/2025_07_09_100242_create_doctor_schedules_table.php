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
        Schema::create('doctor_schedules', function (Blueprint $table) {
            $table->id();
            $table->key(); // UUID yaradır

            // Əlaqələr
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Həkim
            $table->foreignId('clinic_id')->constrained()->onDelete('cascade'); // Klinika

            // Cədvəl məlumatları
            $table->enum('type', ['one_time', 'recurring'])->default('recurring'); // Birdəfəlik/Təkrarlanan
            $table->date('start_date'); // Başlama tarixi
            $table->date('end_date')->nullable(); // Bitmə tarixi (təkrarlanan cədvəl üçün)
            $table->enum('frequency', ['daily', 'weekly', 'monthly'])->nullable(); // Təkrarlanma tezliyi
            $table->integer('interval')->default(1); // Təkrarlanma intervalı (1 = hər həftə, 2 = hər iki həftə və s.)
            $table->json('days_of_week')->nullable(); // Həftənin günləri (təkrarlanan cədvəl üçün) - [1,2,3,4,5]
            $table->time('start_time'); // Başlama saatı
            $table->time('end_time'); // Bitmə saatı
            $table->integer('slot_duration')->default(30); // Hər bir randevu aralığının müddəti (dəqiqə)
            $table->integer('break_time')->default(0); // Randevular arasındakı fasilə (dəqiqə)
            $table->json('meta_data')->nullable(); // Əlavə məlumatlar

            // Cədvəlin statusu
            $table->boolean('is_active')->default(true); // Aktiv/Deaktiv

            $table->timestamps();
            $table->softDeletes();

            // İndekslər
            $table->index(['user_id', 'clinic_id', 'is_active']);
            $table->index(['start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctor_schedules');
    }
};
