<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('treatment_plans', function (Blueprint $table) {
            $table->id();
            $table->key();
            $table->foreignId('appointment_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->comment('Patient ID')->onDelete('cascade');
            $table->foreignId('diagnosis_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title'); // Plan başlığı
            $table->text('description'); // Ətraflı təsvir
            $table->date('start_date'); // Başlama tarixi
            $table->date('end_date')->nullable(); // Bitmə tarixi
            $table->string('status')->default('active'); // active, completed, cancelled
            $table->text('goals')->nullable(); // Məqsədlər
            $table->text('notes')->nullable(); // Əlavə qeydlər
            $table->customField();
            $table->trackable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('treatment_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('treatment_plan_id')->constrained()->onDelete('cascade');
            $table->string('title'); // Addım başlığı
            $table->text('description')->nullable(); // Təsvir
            $table->integer('order'); // Sıra
            $table->string('status')->default('pending'); // pending, in_progress, completed
            $table->date('due_date')->nullable(); // Son tarix
            $table->date('completed_date')->nullable(); // Tamamlanma tarixi
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('treatment_steps');
        Schema::dropIfExists('treatment_plans');
    }
};
