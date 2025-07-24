<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rəy faydalılıq qeydləri cədvəlini yaradır.
     * Bu cədvəl istifadəçilərin hansı rəyləri faydalı hesab etdiklərini saxlayır.
     * @return void
     */
    public function up(): void
    {
        Schema::create('review_helpful', function (Blueprint $table) {
            $table->id(); // Qeydin unikal ID-si
            $table->foreignId('review_id')->constrained()->onDelete('cascade'); // Rəy əlaqəsi
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // İstifadəçi əlaqəsi
            $table->boolean('is_helpful'); // Rəy faydalıdır?
            $table->timestamps(); // Yaradılma və yenilənmə vaxtları

            // Eyni istifadəçi-rəy cütlüyünün unikallığı
            $table->unique(['user_id', 'review_id']);
        });
    }

    /**
     * Rəy faydalılıq qeydləri cədvəlini silir.
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('review_helpful');
    }
};
