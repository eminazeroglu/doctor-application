<?php

namespace Database\Seeders;

use App\Enums\InvoiceStatusEnum;
use App\Enums\PaymentMethodEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\RefundReasonEnum;
use App\Enums\RefundStatusEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Enums\ProviderRequestTypeEnum;
use App\Models\User;
use App\Models\Payment;
use App\Models\Invoice;
use App\Models\PaymentProvider;
use App\Models\PaymentTransaction;
use App\Models\Refund;
use App\Models\PaymentProviderLog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PaymentSeeder extends Seeder
{
    /**
     * Payment modulu üçün test məlumatları yaradır.
     * Randevu ödənişləri, fakturaları və əlaqəli məlumatları yaradır.
     */
    public function run(): void
    {
        $this->command->info('Payment modulu seedlənməsi başladı...');

        // 1. Əvvəlcə mövcud məlumatları silirik
        $this->clearExistingData();

        // 2. Ödəniş təchizatçılarını yaradırıq
        $this->createPaymentProviders();

        // 3. Ödənişləri yaradırıq
        $this->createPayments();

        $this->command->info('Payment modulu uğurla seedləndi!');
    }

    /**
     * Payment modulu ilə əlaqəli bütün məlumatları silir
     */
    private function clearExistingData(): void
    {
        $this->command->info('Mövcud payment məlumatları silinir...');

        // Foreign key constraint-ləri müvəqqəti olaraq söndürürük
        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Cədvəlləri düzgün ardıcıllıqla təmizləyirik
        \DB::table('payment_provider_logs')->truncate();
        \DB::table('refunds')->truncate();
        \DB::table('payment_transactions')->truncate();
        \DB::table('invoices')->truncate();
        \DB::table('payments')->truncate();
        \DB::table('payment_providers')->truncate();

        // Foreign key constraint-ləri yenidən aktivləşdiririk
        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->command->info('Mövcud məlumatlar silindi.');
    }

    /**
     * Ödəniş təchizatçılarını yaradır (Kapital Bank, PASHA Bank)
     */
    private function createPaymentProviders(): void
    {
        $this->command->info('Ödəniş təchizatçıları yaradılır...');

        $providers = [
            [
                'name' => 'Kapital Bank',
                'code' => 'kapital_bank',
                'description' => 'Kapital Bank ödəniş gateway',
                'photo_path' => 'kapital-bank.png',
                'configuration' => [
                    'merchant_id' => 'TEST_MERCHANT_001',
                    'secret_key' => 'test_secret_key_kapital',
                    'api_url' => 'https://test-api.kapitalbank.az',
                    'success_url' => url('/payment/success'),
                    'failure_url' => url('/payment/failure'),
                    'currency' => 'AZN'
                ],
                'is_active' => true,
                'is_test_mode' => true,
            ],
            [
                'name' => 'PASHA Bank',
                'code' => 'pasha_bank',
                'description' => 'PASHA Bank ödəniş gateway',
                'photo_path' => 'pasha-bank.png',
                'configuration' => [
                    'merchant_id' => 'TEST_MERCHANT_002',
                    'secret_key' => 'test_secret_key_pasha',
                    'api_url' => 'https://test-api.pashabank.az',
                    'success_url' => url('/payment/success'),
                    'failure_url' => url('/payment/failure'),
                    'currency' => 'AZN'
                ],
                'is_active' => true,
                'is_test_mode' => true,
            ]
        ];

        foreach ($providers as $providerData) {
            PaymentProvider::create($providerData);
        }

        $this->command->info('2 ödəniş təchizatçısı yaradıldı.');
    }

    /**
     * Ödənişləri və əlaqəli məlumatları yaradır
     */
    private function createPayments(): void
    {
        $this->command->info('Ödənişlər yaradılır...');

        // Mövcud istifadəçiləri əldə edirik
        $users = User::inRandomOrder()->limit(50)->get();
        $providers = PaymentProvider::all();

        if ($users->isEmpty()) {
            $this->command->error('İstifadəçilər tapılmadı! Əvvəl UserSeeder işə salın.');
            return;
        }

        $statusDistribution = $this->getStatusDistribution();

        for ($i = 0; $i < 100; $i++) {
            $user = $users->random();
            $provider = $providers->random();
            $status = $statusDistribution[array_rand($statusDistribution)];

            // Ödəniş yaradırıq
            $payment = $this->createSinglePayment($user, $status);

            // Faktura yaradırıq
            $invoice = $this->createInvoiceForPayment($payment);

            // Əməliyyat yaradırıq
            $transaction = $this->createTransactionForPayment($payment, $provider);

            // Provider log yaradırıq
            $this->createProviderLogForTransaction($transaction, $provider);

            // Bəzi completed ödənişlər üçün geri ödəmə yaradırıq (5% ehtimal)
            if ($status === PaymentStatusEnum::Completed && rand(1, 100) <= 5) {
                $this->createRefundForPayment($payment);
            }
        }

        $this->command->info('100 ödəniş və əlaqəli məlumatlar yaradıldı.');
    }

    /**
     * Status paylanmasını qaytarır (80% completed, 20% pending)
     */
    private function getStatusDistribution(): array
    {
        $statuses = [];

        // 80% completed
        for ($i = 0; $i < 80; $i++) {
            $statuses[] = PaymentStatusEnum::Completed;
        }

        // 20% pending
        for ($i = 0; $i < 20; $i++) {
            $statuses[] = PaymentStatusEnum::Pending;
        }

        return $statuses;
    }

    /**
     * Tək ödəniş yaradır
     */
    private function createSinglePayment(User $user, string $status): Payment
    {
        $amount = rand(5000, 10000) / 100; // 50.00 - 100.00 AZN
        $methods = [PaymentMethodEnum::Card, PaymentMethodEnum::EWallet];

        $paymentData = [
            'uuid' => Str::uuid(),
            'user_id' => $user->id,
            'amount' => $amount,
            'currency' => 'AZN',
            'payment_method' => $methods[array_rand($methods)],
            'payment_status' => $status,
            'description' => 'Həkim randevu ödənişi',
            'transaction_id' => $status === PaymentStatusEnum::Completed ? 'TXN_' . Str::random(12) : null,
            'paid_at' => $status === PaymentStatusEnum::Completed ? now()->subDays(rand(1, 30)) : null,
            'expires_at' => $status === PaymentStatusEnum::Pending ? now()->addHours(24) : null,
            'additional_info' => [
                'appointment_type' => 'general_consultation',
                'doctor_speciality' => fake()->randomElement(['cardiology', 'neurology', 'dermatology', 'pediatrics']),
                'clinic_name' => fake()->company(),
                'appointment_date' => now()->addDays(rand(1, 7))->format('Y-m-d'),
                'patient_notes' => fake()->optional()->sentence()
            ]
        ];

        return Payment::create($paymentData);
    }

    /**
     * Ödəniş üçün faktura yaradır
     */
    private function createInvoiceForPayment(Payment $payment): Invoice
    {
        $subtotal = $payment->amount;
        $tax = round($subtotal * 0.18, 2); // 18% ƏDV
        $discount = 0;
        $total = $subtotal + $tax - $discount;

        // Tarix məlumatlarını düzgün əldə edirik
        $invoiceDate = now()->subDays(rand(1, 30));
        $dueDate = $invoiceDate->copy()->addDays(7);

        $invoiceData = [
            'uuid' => Str::uuid(),
            'payment_id' => $payment->id,
            'invoice_number' => 'INV-' . date('Y') . '-' . str_pad($payment->id, 6, '0', STR_PAD_LEFT),
            'invoice_date' => $invoiceDate->toDateString(),
            'due_date' => $dueDate->toDateString(),
            'status' => $payment->payment_status === PaymentStatusEnum::Completed ? InvoiceStatusEnum::Paid : InvoiceStatusEnum::Sent,
            'subtotal' => $subtotal,
            'tax' => $tax,
            'discount' => $discount,
            'total' => $total,
            'notes' => 'Həkim xidməti üçün faktura',
            'items' => [
                [
                    'description' => 'Həkim konsultasiyası',
                    'quantity' => 1,
                    'unit_price' => $subtotal,
                    'total' => $subtotal
                ]
            ],
            'billing_details' => [
                'customer_name' => $payment->user->fullname,
                'customer_email' => $payment->user->email,
                'customer_phone' => $payment->user->phone,
                'billing_address' => fake()->address(),
                'company_name' => 'Doctap.az',
                'company_address' => 'Bakı, Azərbaycan',
                'tax_number' => '1234567890'
            ]
        ];

        return Invoice::create($invoiceData);
    }

    /**
     * Ödəniş üçün əməliyyat yaradır
     */
    private function createTransactionForPayment(Payment $payment, PaymentProvider $provider): PaymentTransaction
    {
        $transactionData = [
            'uuid' => Str::uuid(),
            'payment_id' => $payment->id,
            'transaction_type' => TransactionTypeEnum::Payment,
            'transaction_id' => $payment->transaction_id ?: 'TXN_' . Str::random(12),
            'processor' => $provider->code,
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'status' => $payment->payment_status === PaymentStatusEnum::Completed ? TransactionStatusEnum::Completed : TransactionStatusEnum::Pending,
            'response' => $payment->payment_status === PaymentStatusEnum::Completed ?
                'Transaction completed successfully' : 'Transaction is pending',
            'additional_info' => [
                'provider_response_code' => $payment->payment_status === PaymentStatusEnum::Completed ? '00' : '01',
                'provider_message' => $payment->payment_status === PaymentStatusEnum::Completed ? 'Success' : 'Pending',
                'card_mask' => fake()->creditCardNumber(),
                'authorization_code' => Str::random(6)
            ],
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent()
        ];

        return PaymentTransaction::create($transactionData);
    }

    /**
     * Əməliyyat üçün provider log yaradır
     */
    private function createProviderLogForTransaction(PaymentTransaction $transaction, PaymentProvider $provider): PaymentProviderLog
    {
        $logData = [
            'uuid' => Str::uuid(),
            'payment_provider_id' => $provider->id,
            'transaction_id' => $transaction->transaction_id,
            'payment_id' => $transaction->payment_id,
            'request_type' => ProviderRequestTypeEnum::Payment,
            'request_data' => json_encode([
                'amount' => $transaction->amount,
                'currency' => $transaction->currency,
                'merchant_id' => $provider->getConfig('merchant_id'),
                'order_id' => $transaction->payment_id,
                'success_url' => $provider->getConfig('success_url'),
                'failure_url' => $provider->getConfig('failure_url')
            ]),
            'response_data' => json_encode([
                'status' => $transaction->status === TransactionStatusEnum::Completed ? 'success' : 'pending',
                'transaction_id' => $transaction->transaction_id,
                'amount' => $transaction->amount,
                'currency' => $transaction->currency,
                'response_code' => $transaction->status === TransactionStatusEnum::Completed ? '00' : '01',
                'message' => $transaction->status === TransactionStatusEnum::Completed ? 'Transaction successful' : 'Transaction pending'
            ]),
            'response_code' => $transaction->status === TransactionStatusEnum::Completed ? '200' : '202',
            'status' => $transaction->status === TransactionStatusEnum::Completed ? 'success' : 'pending',
            'error_message' => $transaction->status === TransactionStatusEnum::Failed ? 'Transaction failed' : null,
            'ip_address' => $transaction->ip_address
        ];

        return PaymentProviderLog::create($logData);
    }

    /**
     * Ödəniş üçün geri ödəmə yaradır
     */
    private function createRefundForPayment(Payment $payment): Refund
    {
        $refundAmount = $payment->amount * rand(50, 100) / 100; // 50%-100% arası geri ödəmə
        $reasons = [RefundReasonEnum::CustomerRequest, RefundReasonEnum::ServiceNotProvided];

        $refundData = [
            'uuid' => Str::uuid(),
            'payment_id' => $payment->id,
            'amount' => $refundAmount,
            'reason' => $reasons[array_rand($reasons)],
            'notes' => 'Müştəri tələbi əsasında geri ödəmə',
            'status' => RefundStatusEnum::Completed,
            'refund_id' => 'REF_' . Str::random(10),
            'processed_by' => 1, // Admin user ID
            'processed_at' => now()->subDays(rand(1, 15))
        ];

        return Refund::create($refundData);
    }
}
