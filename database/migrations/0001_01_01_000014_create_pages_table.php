<?php

use App\Enums\PageTypeEnum;
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
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->key(); // uuid istifadə edir
            $table->slug(); // slug istifadə edir
            $table->status(); // is_active statusu istifadə edir
            $table->translates(); // Çoxdilli məzmun üçün (name və content burada olacaq)
            $table->photo(); // HasImage trait ilə işləmək üçün
            $table->enum('type', PageTypeEnum::getValues())->default(PageTypeEnum::STANDARD);
            $table->boolean('is_system')->default(false); // Sistem tərəfindən yaradılan səhifələri qeyd etmək üçün
            $table->trackable(); // created_by və updated_by
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('page_widgets', function (Blueprint $table) {
            $table->id();
            $table->key(); // uuid istifadə edir
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->enum('type', \App\Enums\WidgetTypeEnum::getValues());
            $table->integer('order')->default(0);
            $table->status(); // is_active
            $table->translates(); // Widget tərcümələri üçün
            $table->photo(); // Widget şəkli üçün
            $table->json('data')->nullable(); // Widget-spesifik məlumatlar
            $table->trackable(); // created_by və updated_by
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_widgets');
        Schema::dropIfExists('pages');
    }
};
