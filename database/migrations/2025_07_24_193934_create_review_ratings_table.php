<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rəy kriteriya qiymətləndirmələri cədvəlini yaradır.
     * Bu cədvəl rəylərdə müxtəlif kriteriyalar üzrə verilən qiymətləndirmələri saxlayır.
     * @return void
     */
    public function up(): void
    {
        Schema::create('review_ratings', function (Blueprint $table) {
            $table->id(); // Qiymətləndirmənin unikal ID-si
            $table->foreignId('review_id')->constrained()->onDelete('cascade'); // Rəy əlaqəsi
            $table->foreignId('criteria_id')->constrained('review_criteria')->onDelete('cascade'); // Kriteriya əlaqəsi
            $table->integer('rating'); // Qiymətləndirmə (1-5 arası)
            $table->timestamps(); // Yaradılma və yenilənmə vaxtları

            // Eyni rəy-kriteriya cütlüyünün unikallığı
            $table->unique(['review_id', 'criteria_id']);
        });
    }

    /**
     * Rəy kriteriya qiymətləndirmələri cədvəlini silir.
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('review_ratings');
    }
};
