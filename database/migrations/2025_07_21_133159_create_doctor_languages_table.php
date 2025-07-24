<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Həkim dil bilikləri cədvəlini yaradır.
     * Bu cədvəl həkimlərin bildikləri dilləri saxlayır.
     * @return void
     */
    public function up(): void
    {
        Schema::create('doctor_languages', function (Blueprint $table) {
            $table->id(); // Qeydin unikal ID-si
            $table->foreignId('doctor_id')->constrained()->onDelete('cascade'); // Həkim əlaqəsi
            $table->string('language'); // Dilin adı
            $table->string('proficiency')->default('native'); // Bilik səviyyəsi (native, fluent, intermediate, basic)
            $table->timestamps(); // Yaradılma və yenilənmə vaxtları
        });
    }

    /**
     * Həkim dil bilikləri cədvəlini silir.
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('doctor_languages');
    }
};
