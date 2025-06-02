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
        Schema::create('attribute_options', function (Blueprint $table) {
            $table->id();
            $table->key();
            $table->slug();
            $table->foreignId('attribute_id')->constrained()->onDelete('cascade');
            $table->parentId();
            $table->translates();
            // Əlavə məlumatlar
            $table->customField();
            /*
                {
                    "icon": "path/to/icon.svg",    // İkon
                    "color": "#FF0000",            // Rəng kodu
                    "description": "Əlavə məlumat", // Təsvir
                    "additional_price": 100,        // Əlavə qiymət
                    "specifications": {}            // Texniki xüsusiyyətlər
                }
            */
            // Xüsusiyyətlər
            $table->boolean('is_default')->default(false); // Default seçim
            $table->boolean('is_active')->default(true);   // Aktiv/Deaktiv
            $table->integer('order')->default(0);          // Sıralama
            $table->timestamps();
            $table->softDeletes();

            // İndekslər və məhdudiyyətlər
            $table->index(['is_active', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attribute_options');
    }
};
