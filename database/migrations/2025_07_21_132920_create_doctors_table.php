<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Həkimlər cədvəlini yaradır.
     * Bu cədvəl həkimlərin əsas profil və peşə məlumatlarını saxlayır.
     * @return void
     */
    public function up(): void
    {
        Schema::create('doctors', function (Blueprint $table) {
            $table->id(); // Həkimin unikal ID-si
            $table->key(); // Unikal UUID
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // İstifadəçi əlaqəsi
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete(); // Əsas ixtisas
            $table->foreignId('sub_category_id')->nullable()->constrained('categories')->nullOnDelete(); // Alt ixtisas
            $table->text('biography')->nullable(); // Həkim haqqında ətraflı məlumat
            $table->decimal('consultation_fee', 10, 2)->nullable(); // Konsultasiya qiyməti
            $table->integer('consultation_duration')->default(30); // Konsultasiya müddəti (dəqiqə)
            $table->json('working_days')->nullable(); // İş günləri
            $table->boolean('is_verified')->default(false); // Həkim təsdiqlənib?
            $table->boolean('is_featured')->default(false); // Önə çıxarılmış həkim?
            $table->integer('years_of_experience')->nullable(); // İş təcrübəsi (il ilə)
            $table->string('practice_license_number')->nullable(); // Həkimlik lisenziya nömrəsi
            $table->string('title')->nullable(); // Titulu (Dr., Prof. və s.)
            /*
             * {
             *  name: "",
             *  address: "",
             *  phone: "",
             *  latitude: "",
             *  longitude: "",
             * }
             * */
            $table->json('workplace')->nullable();
            $table->json('social_media_links')->nullable(); // Sosial media hesabları
            $table->boolean('available_for_home_visit')->default(false); // Ev ziyarəti təklif edir?
            $table->boolean('available_for_online_consultation')->default(false); // Onlayn konsultasiya təklif edir?
            $table->decimal('home_visit_fee', 10, 2)->nullable(); // Ev ziyarəti qiyməti
            $table->decimal('online_consultation_fee', 10, 2)->nullable(); // Onlayn konsultasiya qiyməti
            $table->integer('average_rating')->default(0); // Orta qiymətləndirmə
            $table->integer('total_ratings')->default(0); // Ümumi qiymətləndirmə sayı
            $table->integer('total_patients')->default(0); // Ümumi xəstə sayı
            $table->timestamps(); // Yaradılma və yenilənmə vaxtları
            $table->softDeletes(); // Yumşaq silmə (soft delete) üçün
        });
    }

    /**
     * Həkimlər cədvəlini silir.
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('doctors');
    }
};
