<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bildiriş logları cədvəlini yaradır.
     * Bu cədvəl göndərilən bütün bildirişlərin tarixçəsini saxlayır.
     * @return void
     */
    public function up(): void
    {
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id(); // Logun unikal ID-si
            $table->uuid('uuid')->unique(); // Unikal UUID
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // İstifadəçi əlaqəsi
            $table->string('channel'); // Bildiriş kanalı (email, sms, push, in_app)
            $table->string('recipient'); // Alıcı (e-poçt, telefon, cihaz tokeni)
            $table->string('notification_type'); // Bildiriş növü
            $table->string('template_code')->nullable(); // İstifadə edilən şablon kodu
            $table->string('subject')->nullable(); // Bildiriş mövzusu
            $table->text('content'); // Bildiriş mətni
            $table->boolean('is_successful')->default(false); // Uğurla göndərilib?
            $table->text('error_message')->nullable(); // Xəta mesajı
            $table->timestamp('sent_at'); // Göndərilmə vaxtı
            $table->timestamps(); // Yaradılma və yenilənmə vaxtları
        });
    }

    /**
     * Bildiriş logları cədvəlini silir.
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
