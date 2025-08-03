<?php

use App\Enums\PaymentStatusEnum;
use App\Enums\PaymentMethodEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ödənişlər cədvəlini yaradır.
     * Bu cədvəl sistemdəki bütün ödənişləri saxlayır.
     * @return void
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id(); // Ödənişin unikal ID-si
            $table->uuid('uuid')->unique(); // Unikal UUID
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // İstifadəçi əlaqəsi
            $table->decimal('amount', 10, 2); // Ödəniş məbləği
            $table->string('currency')->default('AZN'); // Valyuta
            $table->string('payment_method')->default(PaymentMethodEnum::Card); // Ödəniş metodu (card, cash, bank_transfer, etc.)
            $table->string('payment_status')->default(PaymentStatusEnum::Pending); // Ödəniş statusu (pending, completed, failed, refunded, etc.)
            $table->string('transaction_id')->nullable(); // Ödəniş prosessorundan alınan tranzaksiya ID-si
            $table->text('description')->nullable(); // Ödəniş təsviri
            $table->timestamp('paid_at')->nullable(); // Ödəniş vaxtı
            $table->timestamp('expires_at')->nullable(); // Ödəniş müddətinin bitməsi vaxtı
            $table->json('additional_info')->nullable(); // Əlavə məlumatlar
            $table->timestamps(); // Yaradılma və yenilənmə vaxtları
            $table->softDeletes(); // Yumşaq silmə (soft delete) üçün
        });
    }

    /**
     * Ödənişlər cədvəlini silir.
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
