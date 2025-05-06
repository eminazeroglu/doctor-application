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
        Schema::create('blocked_credentials', function (Blueprint $table) {
            $table->id();
            $table->key(); // uuid
            $table->string('type'); // BlockedCredentialEnum Blok tipi
            $table->string('value'); // Email, phone və ya IP
            $table->text('reason');
            $table->timestamp('blocked_until')->nullable(); // null = daimi blok
            $table->boolean('is_active')->default(true);
            $table->trackable(); // created_by və updated_by
            $table->timestamps();
            $table->softDeletes();

            // Eyni credential-ın təkrar əlavə edilməməsi üçün
            $table->unique(['type', 'value']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blocked_credentials');
    }
};
