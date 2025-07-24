<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Xəstə allergiya qeydləri cədvəlini yaradır.
     * Bu cədvəl xəstələrin allergiyalarını saxlayır.
     * @return void
     */
    public function up(): void
    {
        Schema::create('patient_allergies', function (Blueprint $table) {
            $table->id(); // Qeydin unikal ID-si
            $table->key(); // Unikal UUID
            $table->foreignId('patient_id')->constrained()->onDelete('cascade'); // Xəstə əlaqəsi
            $table->string('allergen_name'); // Allergen adı
            $table->string('allergen_type'); // Allergen növü (food, drug, environmental, etc.)
            $table->string('severity')->nullable(); // Allergiya şiddəti (mild, moderate, severe)
            $table->text('reactions')->nullable(); // Allergik reaksiyalar
            $table->date('diagnosis_date')->nullable(); // Diaqnoz tarixi
            $table->text('notes')->nullable(); // Əlavə qeydlər
            $table->timestamps(); // Yaradılma və yenilənmə vaxtları

            // Eyni xəstə-allergen cütlüyünün unikallığı
            $table->unique(['patient_id', 'allergen_name']);
        });
    }

    /**
     * Xəstə allergiya qeydləri cədvəlini silir.
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_allergies');
    }
};
