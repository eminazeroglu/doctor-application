<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Xəstələr cədvəlini yaradır.
     * Bu cədvəl xəstələrin əsas tibbi məlumatlarını saxlayır.
     * @return void
     */
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id(); // Xəstənin unikal ID-si
            $table->key(); // Unikal UUID
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // İstifadəçi əlaqəsi
            $table->text('medical_history')->nullable(); // Tibbi tarixçə
            $table->text('allergies')->nullable(); // Allergiyalar
            $table->text('chronic_diseases')->nullable(); // Xroniki xəstəliklər
            $table->text('current_medications')->nullable(); // Hal-hazırda qəbul edilən dərmanlar
            $table->text('family_medical_history')->nullable(); // Ailə tibbi tarixçəsi
            $table->json('additional_info')->nullable(); // Əlavə məlumatlar
            $table->string('blood_type')->nullable(); // Qan qrupu
            $table->float('height')->nullable(); // Boy (sm)
            $table->float('weight')->nullable(); // Çəki (kq)
            $table->string('emergency_contact_name')->nullable(); // Təcili əlaqə şəxsinin adı
            $table->string('emergency_contact_phone')->nullable(); // Təcili əlaqə şəxsinin telefonu
            $table->string('emergency_contact_relation')->nullable(); // Təcili əlaqə şəxsi ilə qohumluq
            $table->string('insurance_provider')->nullable(); // Sığorta şirkəti
            $table->string('insurance_policy_number')->nullable(); // Sığorta polisi nömrəsi
            $table->date('insurance_expiry_date')->nullable(); // Sığorta polisinin bitmə tarixi
            $table->timestamps(); // Yaradılma və yenilənmə vaxtları
            $table->softDeletes(); // Yumşaq silmə (soft delete) üçün
        });
    }

    /**
     * Xəstələr cədvəlini silir.
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
