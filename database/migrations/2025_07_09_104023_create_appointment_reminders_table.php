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
        Schema::create('appointment_reminders', function (Blueprint $table) {
            $table->id();

            // Əlaqələr
            $table->foreignId('appointment_id')->constrained()->onDelete('cascade');

            // Xatırlatma məlumatları
            $table->string('type'); // Xatırlatma növü
            $table->timestamp('scheduled_at'); // Planlaşdırılmış tarix
            $table->timestamp('sent_at')->nullable(); // Göndərilmə tarixi
            $table->boolean('is_sent')->default(false); // Göndərilibmi
            $table->text('message')->nullable(); // Xatırlatma mesajı
            $table->json('meta_data')->nullable(); // Əlavə məlumatlar

            $table->timestamps();

            // İndekslər
            // $table->index(['appointment_id', 'type', 'scheduled_at', 'is_sent']);
			$table->index(
				['appointment_id', 'type', 'scheduled_at', 'is_sent'],
				'appt_reminder_idx' // Custom name under 64 charssss
			);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointment_reminders');
    }
};
