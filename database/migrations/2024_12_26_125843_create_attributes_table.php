<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('attributes', function (Blueprint $table) {
            // Əsas identifikatorlar
            $table->id();
            $table->key();
            $table->slug();

            // Əsas məlumatlar
            /*
             * {
             *      "az": {
             *          "name": "",
             *          "description": "",
             *          "listing_name_prefix": "",
             *          "listing_name_suffix": "",
             *      }
             * }
             * */
            $table->json('translates')->nullable(); // Çoxdilli dəstək

            /*
             * Admin tərəf üçün group (məs:Telefon üçün filter)
             * */
            $table->string('group_name')->nullable();

            // Atribut tipi və validasiya
            $table->string('type'); // AttributeTypeEnum::getValues()

            // Asılılıqlar və əlaqələr
            $table->parentId();

            $table->boolean('is_active')->default(true);        // Aktiv/Deaktiv

            // Sıralama və qruplaşdırma
            $table->integer('order')->default(0);               // Sıralama

            $table->customField();

            // Sistem sütunları
            $table->timestamps();
            $table->softDeletes();

            // İndekslər
            $table->index(['type', 'is_active']);
            $table->index('order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attributes');
    }
};
