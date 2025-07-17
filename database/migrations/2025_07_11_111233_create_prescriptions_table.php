<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->key();
            $table->string('prescription_number')->unique(); // Unikal resept nömrəsi
            $table->foreignId('appointment_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->comment('Patient ID')->onDelete('cascade');
            $table->foreignId('diagnosis_id')->nullable()->constrained()->nullOnDelete();
            $table->date('issue_date'); // Verilmə tarixi
            $table->date('expiry_date')->nullable(); // Bitmə tarixi
            $table->boolean('is_digital')->default(true); // Elektron/kağız
            $table->string('status')->default('active'); // active, completed, cancelled
            $table->text('notes')->nullable(); // Əlavə qeydlər
            $table->customField();
            $table->trackable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('prescription_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prescription_id')->constrained()->onDelete('cascade');
            $table->string('medication_name'); // Dərman adı
            $table->string('dosage')->nullable(); // Dozası
            $table->string('frequency')->nullable(); // Qəbul tezliyi
            $table->string('duration')->nullable(); // Müddəti
            $table->integer('quantity')->nullable(); // Miqdarı
            $table->text('instructions')->nullable(); // Təlimatlar
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescription_items');
        Schema::dropIfExists('prescriptions');
    }
};
