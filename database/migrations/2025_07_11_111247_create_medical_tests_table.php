<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_tests', function (Blueprint $table) {
            $table->id();
            $table->key();
            $table->foreignId('appointment_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->comment('Patient ID')->onDelete('cascade');
            $table->string('test_type'); // lab, imaging, procedure
            $table->string('name'); // Test adı
            $table->text('description')->nullable(); // Təsvir
            $table->string('status')->default('ordered'); // ordered, in_progress, completed, cancelled
            $table->date('ordered_date'); // Sifariş tarixi
            $table->date('due_date')->nullable(); // Son tarix
            $table->date('completed_date')->nullable(); // Tamamlanma tarixi
            $table->text('instructions')->nullable(); // Xüsusi təlimatlar
            $table->customField();
            $table->trackable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('test_results', function (Blueprint $table) {
            $table->id();
            $table->key();
            $table->foreignId('medical_test_id')->constrained()->onDelete('cascade');
            $table->date('result_date'); // Nəticə tarixi
            $table->json('results'); // Test nəticələri
            $table->text('summary')->nullable(); // Qısa xülasə
            $table->text('interpretation')->nullable(); // Həkim interpretasiyası
            $table->boolean('is_abnormal')->default(false); // Normal deyil?
            $table->string('file_path')->nullable(); // Fayl yolu (PDF və s.)
            $table->customField();
            $table->trackable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_results');
        Schema::dropIfExists('medical_tests');
    }
};
