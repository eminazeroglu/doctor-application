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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->key(); // uuid yaradır
            $table->slug(); // slug yaradır

            // Kateqoriya əlaqəsi
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');

            // Çoxdilli məlumatlar üçün
            $table->translates();

            // Əlavə məlumatlar
            $table->decimal('price', 10, 2)->nullable(); // Qiymət
            $table->integer('duration')->nullable(); // Müddət (dəqiqə)
            $table->json('meta_tags')->nullable(); // SEO meta məlumatları
            $table->photo(); // Şəkil

            // Əlavə sahələr
            $table->customField(); // custom_fields JSON sahəsi

            // Status və sıralama
            $table->boolean('is_popular')->default(false); // Populyar xidmət
            $table->boolean('is_active')->default(true); // Aktiv/deaktiv
            $table->integer('order')->default(0); // Sıralama

            $table->timestamps();
            $table->softDeletes();

            // İndekslər
            $table->index(['category_id', 'is_active', 'is_popular', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
