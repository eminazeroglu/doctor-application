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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->key(); // uuid sütunu üçün macro istifadə edirik

            // Model məlumatları
            $table->string('model_type')->nullable(); // Log edilən modelin tipi
            $table->unsignedBigInteger('model_id')->nullable(); // Log edilən modelin ID-si

            // Əməliyyat məlumatları
            $table->string('action'); // ActivityLogActionEnum::getValues() Edilən əməliyyatın növü
            $table->json('old_data')->nullable(); // Dəyişiklikdən əvvəlki data
            $table->json('new_data')->nullable(); // Dəyişiklikdən sonrakı data

            // Request məlumatları
            $table->string('ip_address')->nullable(); // İstifadəçinin IP ünvanı
            $table->string('user_agent')->nullable(); // Browser məlumatları
            $table->string('method')->nullable(); // HTTP metodu (GET, POST, etc.)
            $table->string('url')->nullable(); // Hansı URL-dən edilib
            $table->json('meta_data')->nullable(); // Əlavə meta məlumatlar

            // Status məlumatları
            $table->string('status'); // ActivityLogStatusEnum::getValues() Əməliyyatın statusu
            $table->text('error_message')->nullable(); // Xəta mesajı

            // Audit məlumatları
            $table->trackable(); // created_by və updated_by sütunları üçün macro

            // Timestamps və soft delete
            $table->timestamps();
            $table->softDeletes();

            // İndekslər
            $table->index(['model_type', 'model_id']);
            $table->index('action');
            $table->index('status');
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
