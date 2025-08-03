<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Xəstə dərman qeydləri cədvəlini yaradır.
     * Bu cədvəl xəstələrin qəbul etdiyi dərmanları saxlayır.
     * @return void
     */
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::create('patient_medications', function (Blueprint $table) {
            $table->id(); // Qeydin unikal ID-si
            $table->uuid('uuid')->unique(); // Unikal UUID
            $table->foreignId('patient_id')->constrained()->onDelete('cascade'); // Xəstə əlaqəsi
            $table->foreignId('doctor_id')->nullable()->constrained()->nullOnDelete(); // Həkim əlaqəsi
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete(); // Randevu əlaqəsi
            $table->string('medication_name'); // Dərman adı
            $table->string('dosage')->nullable(); // Dozası
            $table->string('frequency')->nullable(); // Qəbuletmə tezliyi
            $table->text('instructions')->nullable(); // Təlimatlar
            $table->date('start_date'); // Başlama tarixi
            $table->date('end_date')->nullable(); // Bitmə tarixi
            $table->text('reason')->nullable(); // Qəbul səbəbi
            $table->text('side_effects')->nullable(); // Yan təsirlər
            $table->boolean('is_active')->default(true); // Aktiv dərmandır?
            $table->text('notes')->nullable(); // Əlavə qeydlər
            $table->timestamps(); // Yaradılma və yenilənmə vaxtları
        });
        Schema::enableForeignKeyConstraints();
    }

    /**
     * Xəstə dərman qeydləri cədvəlini silir.
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_medications');
    }
};
