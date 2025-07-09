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
        Schema::create('appointment_history', function (Blueprint $table) {
            $table->id();

            // Əlaqələr
            $table->foreignId('appointment_id')->constrained()->onDelete('cascade'); // Randevu
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // İstifadəçi
            $table->string('status')->nullable(); // Status

            // Tarixçə məlumatları
            $table->string('action'); // Əməliyyat (status_changed, note_added və s.)
            $table->text('notes')->nullable(); // Qeydlər
            $table->json('changes')->nullable(); // Dəyişikliklər
            $table->json('meta_data')->nullable(); // Əlavə məlumatlar

            $table->timestamps();

            // İndekslər
            $table->index(['appointment_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointment_history');
    }
};
