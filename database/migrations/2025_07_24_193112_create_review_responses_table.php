<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rəy cavabları cədvəlini yaradır.
     * Bu cədvəl həkim və klinika sahiblərinin rəylərə verdikləri cavabları saxlayır.
     * @return void
     */
    public function up(): void
    {
        Schema::create('review_responses', function (Blueprint $table) {
            $table->id(); // Cavabın unikal ID-si
            $table->foreignId('review_id')->constrained()->onDelete('cascade'); // Rəy əlaqəsi
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Cavab yazan istifadəçi əlaqəsi
            $table->text('response'); // Cavab mətni
            $table->boolean('is_moderated')->default(false); // Cavab moderasiyadan keçib?
            $table->boolean('is_active')->default(true); // Cavab aktivdir?
            $table->timestamps(); // Yaradılma və yenilənmə vaxtları
        });
    }

    /**
     * Rəy cavabları cədvəlini silir.
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('review_responses');
    }
};
