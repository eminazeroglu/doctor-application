<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Klinikalar cədvəlini yaradır.
     * Bu cədvəl platformadakı bütün klinikalar haqqında əsas məlumatları saxlayır.
     * @return void
     */
    public function up(): void
    {
        Schema::create('clinics', function (Blueprint $table) {
            $table->id(); // Klinikanın unikal ID-si
            $table->key(); // Unikal UUID
            $table->string('name'); // Klinikanın adı
            $table->string('slug')->unique(); // SEO-dostu URL
            $table->text('description')->nullable(); // Klinika haqqında ətraflı məlumat
            $table->string('address')->nullable(); // Klinikanın ünvanı
            $table->string('city')->nullable(); // Şəhər
            $table->string('region')->nullable(); // Rayon/bölgə
            $table->string('country')->default('Azerbaijan'); // Ölkə
            $table->string('postal_code')->nullable(); // Poçt indeksi
            $table->string('phone')->nullable(); // Əlaqə telefonu
            $table->string('email')->nullable(); // Əlaqə e-poçtu
            $table->string('website')->nullable(); // Vebsayt
            $table->decimal('latitude', 10, 7)->nullable(); // Xəritədə en dairəsi
            $table->decimal('longitude', 10, 7)->nullable(); // Xəritədə uzunluq dairəsi
            $table->json('working_hours')->nullable(); // İş saatları
            $table->json('facilities')->nullable(); // Təklif olunan imkanlar
            $table->photo('logo'); // Logo faylının yolu
            $table->json('images')->nullable(); // Klinika şəkilləri
            $table->integer('rating')->default(0); // Orta qiymətləndirmə
            $table->integer('ratings_count')->default(0); // Qiymətləndirmə sayı
            $table->boolean('is_verified')->default(false); // Klinika təsdiqlənib?
            $table->boolean('is_featured')->default(false); // Önə çıxarılmış klinika?
            $table->boolean('is_active')->default(true); // Klinika aktivdir?
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); // Yaradıcı istifadəçi
            $table->foreignId('parent_id')->nullable()->constrained('clinics')->nullOnDelete(); // Əsas klinika (filiallar üçün)
            $table->timestamps(); // Yaradılma və yenilənmə vaxtları
            $table->softDeletes(); // Yumşaq silmə (soft delete) üçün
        });
    }

    /**
     * Klinikalar cədvəlini silir.
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('clinics');
    }
};
