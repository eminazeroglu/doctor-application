<?php

use App\Enums\TransactionTypeEnum;
use App\Enums\TransactionStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ödəniş əməliyyatları cədvəlini yaradır.
     * Bu cədvəl ödəniş prosesində baş verən bütün əməliyyatları saxlayır.
     * @return void
     */
    public function up(): void
    {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id(); // Əməliyyatın unikal ID-si
            $table->uuid('uuid')->unique(); // Unikal UUID
            $table->foreignId('payment_id')->constrained()->onDelete('cascade'); // Ödəniş əlaqəsi
            $table->string('transaction_type')->default(TransactionTypeEnum::Payment); // Əməliyyat növü (payment, refund, chargeback, etc.)
            $table->string('transaction_id')->nullable(); // Ödəniş prosessorundan alınan əməliyyat ID-si
            $table->string('processor'); // Ödəniş prosessoru
            $table->decimal('amount', 10, 2); // Əməliyyat məbləği
            $table->string('currency')->default('AZN'); // Valyuta
            $table->string('status')->default(TransactionStatusEnum::Pending); // Əməliyyat statusu (pending, completed, failed, etc.)
            $table->text('response')->nullable(); // Ödəniş prosessorundan alınan cavab
            $table->json('additional_info')->nullable(); // Əlavə məlumatlar
            $table->ipAddress('ip_address')->nullable(); // İP ünvanı
            $table->string('user_agent')->nullable(); // İstifadəçi agent məlumatı
            $table->timestamps(); // Yaradılma və yenilənmə vaxtları
        });
    }

    /**
     * Ödəniş əməliyyatları cədvəlini silir.
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
