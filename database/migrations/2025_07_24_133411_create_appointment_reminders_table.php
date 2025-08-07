<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Randevu xatırlatmaları cədvəlini yaradır.
     * Bu cədvəl randevu xatırlatmalarını saxlayır.
     * @return void
     */
    public function up(): void
    {
        Schema::create('appointment_reminders', function (Blueprint $table) {
            $table->id(); // Xatırlatmanın unikal ID-si
            $table->foreignId('appointment_id')->constrained()->onDelete('cascade'); // Randevu əlaqəsi
            $table->string('type')->default('email'); // Xatırlatma növü (email, sms, app)
            $table->dateTime('send_at'); // Göndərilmə vaxtı
            $table->boolean('is_sent')->default(false); // Göndərilib?
            $table->dateTime('sent_at')->nullable(); // Göndərilmə vaxtı
            $table->timestamps(); // Yaradılma və yenilənmə vaxtları

            $table->index(
                ['appointment_id', 'type', 'scheduled_at', 'is_sent'],
                'appt_reminder_idx' // Custom name under 64 charssss
            );
        });
    }

    /**
     * Randevu xatırlatmaları cədvəlini silir.
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('appointment_reminders');
    }
};
