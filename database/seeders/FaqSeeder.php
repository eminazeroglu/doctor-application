<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Faq::query()->truncate();
        $faker = \Faker\Factory::create();
        for ($i = 1; $i <= 10; $i++) {
            Faq::query()->create([
                'question' => $faker->sentence,
                'answer' => $faker->paragraph,
            ]);
        }
    }
}
