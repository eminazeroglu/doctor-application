<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ödəniş təchizatçıları cədvəlini yaradır.
     * Bu cədvəl ödəniş gateway təchizatçılarını saxlayır.
     * @return void
     */
    public function up(): void
    {
        Schema::create('payment_providers', function (Blueprint $table) {
            $table->id(); // Təchizatçının unikal ID-si
            $table->string('name'); // Təchizatçı adı
            $table->string('code')->unique(); // Təchizatçı kodu
            $table->text('description')->nullable(); // Təchizatçı təsviri
            $table->photo(); // Logo faylının yolu
            $table->json('configuration')->nullable(); // Təchizatçı konfiqurasiyası
            $table->boolean('is_active')->default(true); // Təchizatçı aktivdir?
            $table->boolean('is_test_mode')->default(false); // Test rejimindədir?
            $table->timestamps(); // Yaradılma və yenilənmə vaxtları
        });
    }

    /**
     * Ödəniş təchizatçıları cədvəlini silir.
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_providers');
    }
};
