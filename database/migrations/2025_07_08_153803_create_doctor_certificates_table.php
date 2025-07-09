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
        Schema::create('doctor_certificates', function (Blueprint $table) {
            $table->id();
            $table->key(); // UUID yaradır

            // Əlaqələr
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Sertifikat məlumatları
            $table->string('name'); // Sertifikat adı
            $table->string('issuer'); // Verən qurum
            $table->date('issue_date')->nullable(); // Verilmə tarixi
            $table->date('expiry_date')->nullable(); // Bitmə tarixi (varsa)
            $table->string('file_path')->nullable(); // Sertifikat faylı
            $table->boolean('is_verified')->default(false); // Təsdiqlənibmi
            $table->timestamp('verified_at')->nullable(); // Təsdiqlənmə tarixi
            $table->text('description')->nullable(); // Əlavə məlumat
            $table->json('custom_fields')->nullable(); // Əlavə məlumatlar

            // İş ardıcıllığı
            $table->integer('order')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctor_certificates');
    }
};
