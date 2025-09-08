<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function createNotificationsTable(): void
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

    public function createNotificationTemplatesTable(): void
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

    public function createNotificationPreferences(): void
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

    public function createNotificationDevices(): void
    {
        Schema::create('notification_devices', function (Blueprint $table) {
            $table->id(); // Cihazın unikal ID-si
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // İstifadəçi əlaqəsi
            $table->string('device_token'); // Cihaz tokeni
            $table->string('device_type'); // Cihaz növü (ios, android, web)
            $table->string('device_name')->nullable(); // Cihaz adı
            $table->string('app_version')->nullable(); // Tətbiq versiyası
            $table->boolean('is_active')->default(true); // Cihaz aktivdir?
            $table->timestamp('last_used_at')->nullable(); // Son istifadə vaxtı
            $table->timestamps(); // Yaradılma və yenilənmə vaxtları

            // Eyni cihaz tokeninin unikallığı
            $table->unique(['device_token']);
        });
    }

    public function createNotificationLogs(): void
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

    public function up(): void
    {
        $this->createNotificationsTable();
        $this->createNotificationTemplatesTable();
        $this->createNotificationPreferences();
        $this->createNotificationDevices();
        $this->createNotificationLogs();
    }

    /**
     * Bildirişlər cədvəlini silir.
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('notification_templates');
        Schema::dropIfExists('notification_preferences');
        Schema::dropIfExists('notification_devices');
        Schema::dropIfExists('notification_logs');
    }
};
