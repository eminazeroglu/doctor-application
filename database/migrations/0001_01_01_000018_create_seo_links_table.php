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
        Schema::create('seo_links', function (Blueprint $table) {
            $table->id();
            $table->key(); // uuid
            $table->code(); // unique kod

            // URL identifikatoru - bu URL-ə əsasən SEO məlumatlarını tapacağıq
            $table->string('url')->unique();

            // Polymorph əlaqə - müxtəlif modellərlə əlaqə qura bilmək üçün
            $table->nullableMorphs('seoable');

            // Basic meta məlumatları
            $table->json('basic_meta')->nullable()->comment(
                'title, description, keywords, robots, viewport'
            );

            // OpenGraph məlumatları
            $table->json('open_graph')->nullable()->comment(
                'og:title, og:description, og:image, og:type'
            );

            // Twitter Card məlumatları
            $table->json('twitter')->nullable()->comment(
                'twitter:card, twitter:title, twitter:description, twitter:image'
            );

            // Texniki meta məlumatları
            $table->json('technical')->nullable()->comment(
                'canonical, content-type, language'
            );

            // Xüsusi meta taglar
            $table->json('custom_tags')->nullable();

            // Filter data
            $table->json('filter_data')->nullable();

            // SEO analizi və tarixçə
            $table->integer('score')->default(0); // SEO score
            $table->json('analysis')->nullable(); // Analiz nəticələri
            $table->json('history')->nullable(); // Dəyişiklik tarixçəsi

            // Sitemap üçün əlavə məlumatlar
            $table->boolean('is_sitemap')->default(true);
            $table->string('sitemap_priority')->default('0.5');
            $table->string('sitemap_frequency')->default('weekly');

            // Status və tracking
            $table->boolean('is_active')->default(true);
            $table->trackable();
            $table->timestamps();
            $table->softDeletes();

            // İndekslər
            $table->index('is_active');
            $table->index('is_sitemap');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seo_links');
    }
};
