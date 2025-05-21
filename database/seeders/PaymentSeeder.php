<?php

namespace Database\Seeders;

use App\Enums\PaymentMethodEnum;
use App\Enums\PaymentStatusEnum;
use App\Models\Payment;
use App\Services\Module\UserService;
use Carbon\Carbon;
use Faker\Factory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PaymentSeeder extends Seeder
{
    public $faker;
    public $users;

    /**
     * Seed the payments table.
     */
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        DB::table('payments')->truncate();

        $this->faker = Factory::create();
        $this->users = app(UserService::class)->findActiveList();

        $this->createPayments();

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Ödənişləri yaradır
     */
    public function createPayments(): void
    {
        // Mövcud ListingPaymentService qeydlərini alırıq (əgər varsa)
        $listingServices = [];

        foreach (range(1, 50) as $i) {
            $user = $this->users->random();
            $paymentDate = $this->faker->dateTimeBetween('-3 months', 'now');
            $status = $this->faker->randomElement([
                PaymentStatusEnum::Pending,
                PaymentStatusEnum::Completed,
                PaymentStatusEnum::Failed,
                PaymentStatusEnum::Refunded,
            ]);

            $payment = Payment::create([
                'user_id' => $user->id,
                'paymentable_type' => 'App\\Models\\ListingPaymentService',
                'paymentable_id' => 1,
                'amount' => $this->faker->randomFloat(2, 10, 500),
                'currency' => $this->faker->randomElement(['AZN', 'USD', 'EUR']),
                'status' => $status,
                'payment_method' => PaymentMethodEnum::getRandomValue(),
                'transaction' => $this->faker->boolean(70) ? [
                    'id' => 'txn_' . Str::random(10),
                    'provider' => $this->faker->randomElement(['stripe', 'paypal', 'bank']),
                    'status' => $status,
                ] : null,
                'paid_at' => $status === PaymentStatusEnum::Completed ? $paymentDate : null,
                'description' => $this->faker->sentence,
                'custom_fields' => $this->faker->boolean(30) ? ['note' => $this->faker->word] : null,
                'created_at' => $paymentDate,
                'updated_at' => $paymentDate,
            ]);

        }
    }
}
