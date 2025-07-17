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

        // Həkim - Xidmət əlaqəsi üçün pivot cədvəl
        Schema::create('doctor_service', function (Blueprint $table) {
            $table->id();

            // Əlaqələr
            $table->foreignId('doctor_id')
                ->constrained('users')
                ->onDelete('cascade');

            $table->foreignId('service_id')
                ->constrained('services')
                ->onDelete('cascade');

            // Həkimin bu xidmət üçün fərdi məlumatları
            $table->decimal('custom_price', 10, 2)->nullable(); // Fərdi qiymət
            $table->integer('custom_duration')->nullable(); // Fərdi müddət
            $table->json('custom_fields')->nullable(); // Əlavə məlumatlar

            $table->timestamps();

            // Unikallıq - bir həkim eyni xidməti yalnız bir dəfə təqdim edə bilər
            $table->unique(['doctor_id', 'service_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctor_service');
        Schema::dropIfExists('services');
    }
};
