<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bildirişlər cədvəlini yaradır.
     * Bu cədvəl sistemdəki bütün bildirişləri saxlayır.
     * @return void
     */
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id(); // Bildirişin unikal ID-si
            $table->uuid('uuid')->unique(); // Unikal UUID
            $table->string('type'); // Bildiriş növü (appointment, review, message, etc.)
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // İstifadəçi əlaqəsi
            $table->string('title'); // Bildiriş başlığı
            $table->text('content'); // Bildiriş mətni
            $table->string('icon')->nullable(); // Bildiriş ikonu
            $table->string('action_url')->nullable(); // Bildiriş kliki ilə açılacaq URL
            $table->string('action_text')->nullable(); // Bildiriş düyməsi mətni
            $table->json('data')->nullable(); // Əlavə məlumatlar
            $table->timestamp('read_at')->nullable(); // Oxunma vaxtı
            $table->timestamp('send_at')->nullable(); // Göndərilmə vaxtı
            $table->boolean('is_sent')->default(false); // Göndərilib?
            $table->timestamps(); // Yaradılma və yenilənmə vaxtları
        });
    }

    /**
     * Bildirişlər cədvəlini silir.
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
