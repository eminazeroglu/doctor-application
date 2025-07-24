<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rəy şikayətləri cədvəlini yaradır.
     * Bu cədvəl istifadəçilərin rəylər haqqında etdikləri şikayətləri saxlayır.
     * @return void
     */
    public function up(): void
    {
        Schema::create('review_reports', function (Blueprint $table) {
            $table->id(); // Şikayətin unikal ID-si
            $table->foreignId('review_id')->constrained()->onDelete('cascade'); // Rəy əlaqəsi
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Şikayət edən istifadəçi əlaqəsi
            $table->string('reason'); // Şikayət səbəbi
            $table->boolean('is_resolved')->default(false); // Şikayət həll olunub?
            $table->text('resolution_notes')->nullable(); // Həll qeydləri
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete(); // Həll edən istifadəçi əlaqəsi
            $table->timestamp('resolved_at')->nullable(); // Həll vaxtı
            $table->timestamps(); // Yaradılma və yenilənmə vaxtları
        });
    }

    /**
     * Rəy şikayətləri cədvəlini silir.
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('review_reports');
    }
};
