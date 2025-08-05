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
        Schema::create('doctor_attribute_values', function (Blueprint $table) {
            $table->id();
            $table->key();
            // Əsas əlaqələr
            $table->foreignId('doctor_id')->constrained()->onDelete('cascade');
            // Elan silindikdə bütün attribute dəyərləri də silinir

            $table->foreignId('attribute_id')->constrained()->onDelete('cascade');
            // Attribute silindikdə ona aid olan bütün dəyərlər silinir

            $table->foreignId('attribute_option_id')->nullable()->constrained()->onDelete('cascade');
            // Select tipli atributlar üçün seçilmiş option. Məsələn: "Marka" atributu üçün "BMW" seçimi

            // Dəyər - müxtəlif tip atributlar üçün
            $table->text('value')->nullable();
            // Text tipli atributlar üçün mətn dəyəri
            $table->customField();           // Əlavə xüsusi məlumatlar
            $table->timestamps();

            // İndekslər
            $table->index(['doctor_id', 'attribute_id']);
            $table->index(['attribute_id', 'attribute_option_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctor_attribute_values');
    }
};
