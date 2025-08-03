<?php

use App\Enums\ProviderRequestTypeEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ödəniş təchizatçıları logları cədvəlini yaradır.
     * Bu cədvəl ödəniş təchizatçıları ilə əlaqəli bütün sorğu və cavabları saxlayır.
     * @return void
     */
    public function up(): void
    {
        Schema::create('payment_provider_logs', function (Blueprint $table) {
            $table->id(); // Logun unikal ID-si
            $table->uuid('uuid')->unique(); // Unikal UUID
            $table->foreignId('payment_provider_id')->nullable()->constrained()->nullOnDelete(); // Ödəniş təchizatçısı əlaqəsi
            $table->string('transaction_id')->nullable(); // Əməliyyat ID-si
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete(); // Ödəniş əlaqəsi
            $table->string('request_type')->default(ProviderRequestTypeEnum::Payment); // Sorğu növü (payment, refund, status_check, etc.)
            $table->text('request_data'); // Sorğu məlumatları
            $table->text('response_data')->nullable(); // Cavab məlumatları
            $table->string('response_code')->nullable(); // Cavab kodu
            $table->string('status'); // Sorğu statusu (success, error)
            $table->text('error_message')->nullable(); // Xəta mesajı
            $table->ipAddress('ip_address')->nullable(); // İP ünvanı
            $table->timestamps(); // Yaradılma və yenilənmə vaxtları
        });
    }

    /**
     * Ödəniş təchizatçıları logları cədvəlini silir.
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_provider_logs');
    }
};
