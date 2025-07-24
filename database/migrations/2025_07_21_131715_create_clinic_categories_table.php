<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Klinika ixtisasları cədvəlini yaradır.
     * Bu cədvəl klinikada mövcud olan ixtisasları saxlayır.
     * @return void
     */
    public function up(): void
    {
        Schema::create('clinic_categories', function (Blueprint $table) {
            $table->id(); // Qeydin unikal ID-si
            $table->foreignId('clinic_id')->constrained()->onDelete('cascade'); // Klinika əlaqəsi
            $table->foreignId('category_id')->constrained()->onDelete('cascade'); // İxtisas əlaqəsi
            $table->text('description')->nullable(); // Əlavə təsvir
            $table->boolean('is_active')->default(true); // Aktivdir?
            $table->timestamps(); // Yaradılma və yenilənmə vaxtları

            // Eyni klinika üçün ixtisasın unikallığı
            $table->unique(['clinic_id', 'category_id']);
        });
    }

    /**
     * Klinika ixtisasları cədvəlini silir.
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('clinic_categories');
    }
};
