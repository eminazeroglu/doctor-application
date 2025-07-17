<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_documents', function (Blueprint $table) {
            $table->id();
            $table->key();
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained()->comment('Patient ID')->onDelete('cascade');
            $table->string('document_type'); // epicrisis, referral, sick_leave, certificate
            $table->string('title'); // Sənəd başlığı
            $table->text('content'); // Sənəd məzmunu
            $table->string('file_path')->nullable(); // PDF yolu
            $table->date('issue_date'); // Verilmə tarixi
            $table->date('expiry_date')->nullable(); // Bitmə tarixi (varsa)
            $table->boolean('is_signed')->default(false); // İmzalanıb?
            $table->customField();
            $table->trackable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_documents');
    }
};
