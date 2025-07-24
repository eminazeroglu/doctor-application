<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Klinika tətil günləri cədvəlini yaradır.
     * Bu cədvəl klinikanın bağlı olduğu xüsusi günləri saxlayır.
     * @return void
     */
    public function up(): void
    {
        Schema::create('clinic_holidays', function (Blueprint $table) {
            $table->id(); // Qeydin unikal ID-si
            $table->foreignId('clinic_id')->constrained()->onDelete('cascade'); // Klinika əlaqəsi
            $table->date('date'); // Tətil tarixi
            $table->string('name')->nullable(); // Tətil adı (bayram və s.)
            $table->text('description')->nullable(); // Əlavə təsvir
            $table->boolean('is_recurring')->default(false); // Hər il təkrarlanır?
            $table->timestamps(); // Yaradılma və yenilənmə vaxtları

            // Eyni klinika üçün tarixin unikallığı
            $table->unique(['clinic_id', 'date']);
        });
    }

    /**
     * Klinika tətil günləri cədvəlini silir.
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('clinic_holidays');
    }
};
