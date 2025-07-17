<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diagnoses', function (Blueprint $table) {
            $table->id();
            $table->key();
            $table->foreignId('appointment_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->comment('Patient ID')->onDelete('cascade');
            $table->string('icd_code')->nullable(); // ICD-10/11 kodu
            $table->string('diagnosis_type')->default('primary'); // primary, secondary, differential
            $table->string('name'); // Diaqnozun adı
            $table->text('description')->nullable(); // Ətraflı təsvir
            $table->boolean('is_chronic')->default(false); // Xroniki xəstəlikdir?
            $table->date('diagnosed_at'); // Diaqnoz qoyulma tarixi
            $table->customField();
            $table->trackable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diagnoses');
    }
};
