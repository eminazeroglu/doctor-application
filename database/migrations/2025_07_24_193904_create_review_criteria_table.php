<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rəy kriteriyaları cədvəlini yaradır.
     * Bu cədvəl rəy sistemində istifadə olunan müxtəlif kriteriyaları saxlayır.
     * @return void
     */
    public function up(): void
    {
        Schema::create('review_criteria', function (Blueprint $table) {
            $table->id(); // Kriteriyanın unikal ID-si
            $table->string('name'); // Kriteriya adı
            $table->string('type'); // Kriteriya növü (doctor, clinic)
            $table->text('description')->nullable(); // Təsviri
            $table->integer('weight')->default(1); // Kriteriya çəkisi (əhəmiyyəti)
            $table->boolean('is_active')->default(true); // Kriteriya aktivdir?
            $table->timestamps(); // Yaradılma və yenilənmə vaxtları
        });
    }

    /**
     * Rəy kriteriyaları cədvəlini silir.
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('review_criteria');
    }
};
