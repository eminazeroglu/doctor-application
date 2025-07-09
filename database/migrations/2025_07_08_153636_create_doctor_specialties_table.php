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
        Schema::create('doctor_specialties', function (Blueprint $table) {
            $table->id();
            $table->key(); // UUID yaradır

            // Əlaqələr
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->constrained()->onDelete('cascade');

            // Əlavə məlumatlar
            $table->boolean('is_primary')->default(false); // Əsas ixtisas
            $table->text('description')->nullable(); // İxtisas haqqında əlavə məlumat
            $table->integer('experience_years')->nullable(); // Bu ixtisasda təcrübə (il)
            $table->json('custom_fields')->nullable(); // Əlavə məlumatlar

            $table->timestamps();

            // Eyni həkim eyni ixtisasa ikinci dəfə sahib ola bilməz
            $table->unique(['user_id', 'category_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctor_specialties');
    }
};
