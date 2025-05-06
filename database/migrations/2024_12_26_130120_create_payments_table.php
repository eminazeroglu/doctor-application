<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */

    public function createPaymentServiceTable(): void
    {
        Schema::create('payment_services', function (Blueprint $table) {
            $table->id();
            $table->key();
            $table->slug();
            $table->string('type'); // PaymentServiceTypeEnum
            $table->string('option_type'); // PaymentServiceOptionTypeEnum
            $table->string('key_name'); // PaymentServiceKeyEnum
            $table->string('icon')->nullable();
            $table->customField();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function createPaymentServiceOptionTable(): void
    {
        Schema::create('payment_service_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_service_id')->constrained()->cascadeOnDelete();
            $table->integer('value')->nullable();
            $table->decimal('amount')->nullable();
            $table->customField();
            $table->timestamps();
        });
    }

    public function createPaymentsTable(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->key();
            $table->code();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->morphs('paymentable'); // Polymorphic əlaqə: hansı xidmətə aid olduğunu göstərir
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('AZN');
            $table->string('status'); // PaymentStatusEnum pending, completed, failed, refunded
            $table->string('payment_method')->nullable(); // kredit kartı, balans və s.
            $table->json('transaction')->nullable(); // Ödəniş provayderindən gələn məlumatlar
            $table->timestamp('paid_at')->nullable();
            $table->text('description')->nullable();
            $table->customField();
            $table->timestamps();
            $table->softDeletes();

            // İndekslər
            $table->index(['user_id', 'status']);
        });
    }


    public function up(): void
    {
        $this->createPaymentServiceTable();
        $this->createPaymentServiceOptionTable();
        $this->createPaymentsTable();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_services');
        Schema::dropIfExists('payment_service_options');
        Schema::dropIfExists('payments');
    }
};
