<?php

use App\Enums\InvoiceStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fakturalar cədvəlini yaradır.
     * Bu cədvəl ödənişlər üçün faktura məlumatlarını saxlayır.
     * @return void
     */
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id(); // Fakturanın unikal ID-si
            $table->uuid('uuid')->unique(); // Unikal UUID
            $table->foreignId('payment_id')->constrained()->onDelete('cascade'); // Ödəniş əlaqəsi
            $table->string('invoice_number')->unique(); // Faktura nömrəsi
            $table->date('invoice_date'); // Faktura tarixi
            $table->date('due_date')->nullable(); // Son ödəniş tarixi
            $table->string('status')->default(InvoiceStatusEnum::Draft); // Faktura statusu (draft, sent, paid, overdue, cancelled)
            $table->decimal('subtotal', 10, 2); // Ara cəm
            $table->decimal('tax', 10, 2)->default(0); // Vergi məbləği
            $table->decimal('discount', 10, 2)->default(0); // Endirim məbləği
            $table->decimal('total', 10, 2); // Ümumi məbləğ
            $table->text('notes')->nullable(); // Əlavə qeydlər
            $table->json('items'); // Faktura elementləri
            $table->json('billing_details')->nullable(); // Fakturalama məlumatları
            $table->timestamps(); // Yaradılma və yenilənmə vaxtları
            $table->softDeletes(); // Yumşaq silmə (soft delete) üçün
        });
    }

    /**
     * Fakturalar cədvəlini silir.
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
