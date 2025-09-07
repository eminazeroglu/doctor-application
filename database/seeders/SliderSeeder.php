<?php

namespace Database\Seeders;

use App\Models\Slider;
use App\Services\Module\TranslationService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SliderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Slider::query()->truncate();
        for ($i = 0; $i < 5; $i++) {

            $languages = app(TranslationService::class)->getActiveLanguages();
            $translates = [];

            foreach ($languages as $language) {
                $translates[$language->locale] = [
                    'title' => fake()->sentence(),
                    'description' => fake()->paragraph(),
                    'button_text' => fake()->word(),
                    'button_link' => fake()->url(),
                ];
            }

            Slider::query()->create([
                'translates' => $translates,
                'photo_path' => 'photo-' . rand(1, 5) . '.jpg',
                'is_active' => true,
            ]);
        }
    }
}
