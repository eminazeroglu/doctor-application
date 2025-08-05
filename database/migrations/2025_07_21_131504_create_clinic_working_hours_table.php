<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Klinika iş saatları cədvəlini yaradır.
     * Bu cədvəl klinika iş saatlarını həftənin günlərinə görə saxlayır.
     * @return void
     */
    public function up(): void
    {
        Schema::create('clinic_working_hours', function (Blueprint $table) {
            $table->id(); // İş saatı qeydinin unikal ID-si
            $table->foreignId('clinic_id')->constrained()->onDelete('cascade'); // Klinika əlaqəsi
            $table->string('day_of_week')->nullable(); // Həftənin günü
            $table->time('open_time')->nullable(); // Açılış saatı
            $table->time('close_time')->nullable(); // Bağlanış saatı
            $table->boolean('is_closed')->default(false); // Bu gün bağlıdır?
            $table->text('note')->nullable(); // Əlavə qeyd
            $table->timestamps(); // Yaradılma və yenilənmə vaxtları
        });
    }

    /**
     * Klinika iş saatları cədvəlini silir.
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('clinic_working_hours');
    }
};
