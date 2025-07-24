<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Xəstə favori həkimləri cədvəlini yaradır.
     * Bu cədvəl xəstələrin favori həkimlərini saxlayır.
     * @return void
     */
    public function up(): void
    {
        Schema::create('patient_favorite_doctors', function (Blueprint $table) {
            $table->id(); // Qeydin unikal ID-si
            $table->foreignId('patient_id')->constrained()->onDelete('cascade'); // Xəstə əlaqəsi
            $table->foreignId('doctor_id')->constrained()->onDelete('cascade'); // Həkim əlaqəsi
            $table->text('note')->nullable(); // Qeyd
            $table->timestamps(); // Yaradılma və yenilənmə vaxtları

            // Eyni xəstə-həkim cütlüyünün unikallığı
            $table->unique(['patient_id', 'doctor_id']);
        });
    }

    /**
     * Xəstə favori həkimləri cədvəlini silir.
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_favorite_doctors');
    }
};
