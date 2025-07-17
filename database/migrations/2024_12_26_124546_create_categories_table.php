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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->key();
            $table->slug();
            $table->parentId();
            /*
             * {
             *      "az": {
             *          "name": "",
             *          "description": "",
             *          "name": "",
             *      }
             * }
             * */
            $table->translates();
            $table->string('icon')->nullable();
            $table->photo();
            /*
             * - title
             * - description
             * - keywords
             * */
            $table->json('meta_tags')->nullable();

            /**
             * active_listing_day = Aktiv olma müddəti (gün)
             * listing_post_video = "Elan əlavə et" səhifəsi üçün izahedici video
             * free_listing_limit = Pulsuz əlavə üçün icazə verilən say
             * post_limit_price = Limiti keçdikdə qiymət
             * show_subcategories_as_filter = Sub-kateqoriyalar filter kimi göstərilsin
             * */
            $table->customField();

            $table->boolean('is_home')->default(false); // Default olaraq seçili olması
            $table->boolean('is_default')->default(false); // Default olaraq seçili olması
            $table->boolean('is_active')->default(true);  // Aktiv olub-olmadığı
            $table->integer('order')->default(0);         // Sıralama

            $table->timestamps();
            $table->softDeletes();

            // İndekslər
            $table->index(['is_active', 'order', 'is_default']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
