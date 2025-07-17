<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('doctor_languages', function (Blueprint $table) {
            $table->id();
            $table->key(); // UUID yaradır

            // Əlaqələr
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Dil məlumatları
            $table->string('language'); // Dil adı
            $table->enum('level', ['beginner', 'intermediate', 'advanced', 'native'])->default('intermediate'); // Dil səviyyəsi
            $table->json('custom_fields')->nullable(); // Əlavə məlumatlar

            $table->timestamps();

            // Eyni həkim eyni dili ikinci dəfə əlavə edə bilməz
            $table->unique(['user_id', 'language']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctor_languages');
    }
};
