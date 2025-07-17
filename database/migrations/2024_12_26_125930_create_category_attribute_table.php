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
        Schema::create('category_attribute', function (Blueprint $table) {
            $table->id();

            // Əlaqələr - burada cascade istifadə edirik çünki kateqoriya və ya attribute silindikdə
            // onların arasındakı əlaqə də avtomatik silinməlidir
            $table->foreignId('category_id')
                ->constrained()
                ->onDelete('cascade');

            $table->foreignId('attribute_id')
                ->constrained()
                ->onDelete('cascade');

            // Validasiya və məhdudiyyətlər
            $table->json('validation_rules')->nullable();

            // Attributun bu kateqoriyaya aid xüsusiyyətləri
            $table->boolean('is_required')->default(false);
            $table->boolean('is_visible')->default(true);
            $table->integer('order')->default(0);

            // Kateqoriyaya xas əlavə məlumatlar
            // Məsələn: placeholder, helper text, və s.
            $table->customField();

            $table->timestamps();

            // Unikallıq - eyni kateqoriyada eyni attribute yalnız bir dəfə ola bilər
            $table->unique(['category_id', 'attribute_id']);

            // İndekslər
            $table->index(['category_id', 'is_visible', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_attribute');
    }
};
