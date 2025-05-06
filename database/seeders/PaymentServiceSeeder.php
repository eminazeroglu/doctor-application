<?php

namespace Database\Seeders;

use App\Enums\PaymentServiceKeyEnum;
use App\Enums\PaymentServiceOptionTypeEnum;
use App\Enums\PaymentServiceTypeEnum;
use App\Models\PaymentService;
use App\Models\PaymentServiceOption;
use App\Services\Module\LanguageService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class PaymentServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $items = [
            [
                'key_name' => PaymentServiceKeyEnum::BUMP,
                'type' => ['listing', 'company'],
                'option_type' => PaymentServiceOptionTypeEnum::Hour,
            ],
            [
                'key_name' => PaymentServiceKeyEnum::VIP,
                'type' => ['listing', 'company'],
                'option_type' => PaymentServiceOptionTypeEnum::Day,
            ],
            [
                'key_name' => PaymentServiceKeyEnum::PREMIUM,
                'type' => ['listing', 'company'],
                'option_type' => PaymentServiceOptionTypeEnum::Day,
            ],
            [
                'key_name' => PaymentServiceKeyEnum::COLOR_FRAME,
                'type' => ['listing'],
                'option_type' => PaymentServiceOptionTypeEnum::Day,
            ],
            [
                'key_name' => PaymentServiceKeyEnum::LARGE_FRAME,
                'type' => ['listing'],
                'option_type' => PaymentServiceOptionTypeEnum::Day,
            ],
            [
                'key_name' => PaymentServiceKeyEnum::ALL_IN_ONE,
                'type' => ['listing'],
                'option_type' => PaymentServiceOptionTypeEnum::Day,
            ]
        ];

        Schema::disableForeignKeyConstraints();
        PaymentService::truncate();
        PaymentServiceOption::truncate();

        $listings = collect($items)->filter(fn($item) => in_array('listing', $item['type']));
        $companies = collect($items)->filter(fn($item) => in_array('company', $item['type']));

        $this->createService($listings, PaymentServiceTypeEnum::Listing);
        $this->createService($companies, PaymentServiceTypeEnum::Company);

        Schema::enableForeignKeyConstraints();

    }

    protected function createService($items, $type): void
    {
        foreach ($items as $item) {
            $payment = PaymentService::query()->create([
                'key_name' => $item['key_name'],
                'type' => $type,
                'option_type' => $item['option_type'],
            ]);

            $options = [
                [
                    'value' => 1,
                    'amount' => 2,
                ],
                [
                    'value' => 3,
                    'amount' => 8,
                ],
                [
                    'value' => 10,
                    'amount' => 15,
                ]
            ];

            $payment->options()->createMany($options);
        }
    }
}
