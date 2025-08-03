<?php

use App\Enums\RefundStatusEnum;
use App\Enums\RefundReasonEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Geri ödəmələr cədvəlini yaradır.
     * Bu cədvəl ödənişlərin geri qaytarılması ilə bağlı məlumatları saxlayır.
     * @return void
     */
    public function up(): void
    {
        Schema::create('refunds', function (Blueprint $table) {
            $table->id(); // Geri ödəmənin unikal ID-si
            $table->uuid('uuid')->unique(); // Unikal UUID
            $table->foreignId('payment_id')->constrained()->onDelete('cascade'); // Ödəniş əlaqəsi
            $table->decimal('amount', 10, 2); // Geri ödəmə məbləği
            $table->string('reason')->default(RefundReasonEnum::CustomerRequest); // Geri ödəmə səbəbi
            $table->text('notes')->nullable(); // Əlavə qeydlər
            $table->string('status')->default(RefundStatusEnum::Pending); // Geri ödəmə statusu (pending, completed, failed)
            $table->string('refund_id')->nullable(); // Ödəniş prosessorundan alınan geri ödəmə ID-si
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete(); // Əməliyyatı icra edən istifadəçi
            $table->timestamp('processed_at')->nullable(); // Əməliyyatın icra vaxtı
            $table->timestamps(); // Yaradılma və yenilənmə vaxtları
        });
    }

    /**
     * Geri ödəmələr cədvəlini silir.
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};
