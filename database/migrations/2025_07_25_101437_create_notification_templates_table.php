<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bildiriş şablonları cədvəlini yaradır.
     * Bu cədvəl bildiriş şablonlarını saxlayır.
     * @return void
     */
    public function up(): void
    {
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id(); // Şablonun unikal ID-si
            $table->string('name'); // Şablon adı
            $table->string('code')->unique(); // Şablon kodu
            $table->string('channel'); // Bildiriş kanalı (email, sms, push, in_app)
            $table->string('type'); // Şablon tipi (appointment, review, system, etc.)
            $table->string('subject')->nullable(); // E-poçt mövzusu (e-poçt üçün)
            $table->text('content'); // Şablon mətni
            $table->json('variables')->nullable(); // Şablonda olan dəyişənlər
            $table->boolean('is_active')->default(true); // Şablon aktivdir?
            $table->timestamps(); // Yaradılma və yenilənmə vaxtları
        });
    }

    /**
     * Bildiriş şablonları cədvəlini silir.
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
    }
};
