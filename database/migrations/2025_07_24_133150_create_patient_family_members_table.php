<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Xəstə ailə üzvləri cədvəlini yaradır.
     * Bu cədvəl xəstələrin ailə üzvlərini saxlayır.
     * @return void
     */
    public function up(): void
    {
        Schema::create('patient_family_members', function (Blueprint $table) {
            $table->id(); // Qeydin unikal ID-si
            $table->key(); // Unikal UUID
            $table->foreignId('patient_id')->constrained()->onDelete('cascade'); // Əsas xəstə əlaqəsi
            $table->foreignId('member_patient_id')->nullable()->constrained('patients')->nullOnDelete(); // Üzv xəstə əlaqəsi
            $table->string('name')->nullable(); // Ad (əgər sistem xəstəsi deyilsə)
            $table->string('surname')->nullable(); // Soyad (əgər sistem xəstəsi deyilsə)
            $table->date('birthdate')->nullable(); // Doğum tarixi
            $table->string('gender')->nullable(); // Cins
            $table->string('relation'); // Qohumluq əlaqəsi (parent, child, spouse, etc.)
            $table->string('phone')->nullable(); // Telefon
            $table->string('email')->nullable(); // E-poçt
            $table->text('notes')->nullable(); // Qeydlər
            $table->boolean('is_emergency_contact')->default(false); // Təcili əlaqə şəxsidir?
            $table->boolean('is_dependent')->default(false); // Asılı şəxsdir?
            $table->timestamps(); // Yaradılma və yenilənmə vaxtları
        });
    }

    /**
     * Xəstə ailə üzvləri cədvəlini silir.
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_family_members');
    }
};
