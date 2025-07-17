<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_notes', function (Blueprint $table) {
            $table->id();
            $table->key(); // uuid yaradır (BlueprintProvider əsasında)
            $table->foreignId('appointment_id')->constrained()->onDelete('cascade');
            $table->text('chief_complaint')->nullable(); // Əsas şikayətlər
            $table->text('symptoms')->nullable(); // Simptomlar
            $table->text('examination_notes')->nullable(); // Müayinə qeydləri
            $table->json('vital_signs')->nullable(); // Vital əlamətlər (temp, təzyiq və s.)
            $table->text('history')->nullable(); // Anamnez
            $table->text('additional_notes')->nullable(); // Əlavə qeydlər
            $table->string('status')->default('draft'); // Status (draft, completed)
            $table->customField(); // Əlavə sahələr
            $table->trackable(); // created_by və updated_by sütunları
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_notes');
    }
};
