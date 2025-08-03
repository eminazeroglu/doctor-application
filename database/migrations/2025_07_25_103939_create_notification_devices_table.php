<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bildiriş cihazları cədvəlini yaradır.
     * Bu cədvəl istifadəçilərin push bildirişləri üçün cihaz məlumatlarını saxlayır.
     * @return void
     */
    public function up(): void
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

    /**
     * Bildiriş cihazları cədvəlini silir.
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_devices');
    }
};
