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
        Schema::create('advertisements', function (Blueprint $table) {
            $table->id();
            $table->key();          // uuid
            $table->string('position');  // AdvertisementPositionEnum - Yerləşmə yeri
            $table->timestamp('expiry_date')->nullable();
            $table->string('background_color')->nullable();
            $table->string('display_type'); // AdvertisementDisplayTypeEnum - home_only, all_pages, selected_categories
            $table->json('selected_categories')->nullable();
            $table->translates();   // çoxdilli məlumatlar üçün
            $table->status();       // is_active
            $table->integer('views')->default(0);
            $table->trackable();    // created_by, updated_by
            $table->timestamps();
            $table->softDeletes();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('advertisements');
    }
};
