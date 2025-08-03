<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bildiriş tənzimləmələri cədvəlini yaradır.
     * Bu cədvəl istifadəçilərin bildiriş tənzimləmələrini saxlayır.
     * @return void
     */
    public function up(): void
    {
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id(); // Tənzimləmənin unikal ID-si
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // İstifadəçi əlaqəsi
            $table->string('notification_type'); // Bildiriş növü (appointment, review, message, etc.)
            $table->boolean('email_enabled')->default(true); // E-poçt bildirişləri aktivdir?
            $table->boolean('sms_enabled')->default(true); // SMS bildirişləri aktivdir?
            $table->boolean('push_enabled')->default(true); // Push bildirişləri aktivdir?
            $table->boolean('in_app_enabled')->default(true); // Tətbiq daxili bildirişlər aktivdir?
            $table->timestamps(); // Yaradılma və yenilənmə vaxtları

            // Eyni istifadəçi-bildiriş növü cütlüyünün unikallığı
            $table->unique(['user_id', 'notification_type']);
        });
    }

    /**
     * Bildiriş tənzimləmələri cədvəlini silir.
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};
