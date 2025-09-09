<?php

namespace Database\Seeders;

use App\Models\Testimonial;
use App\Services\Module\TranslationService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TestimonialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Testimonial::query()->truncate();

        for($i = 1; $i <= 8; $i++) {
            $languages = app(TranslationService::class)->getActiveLanguages();
            $translates = [];

            foreach ($languages as $language) {
                $translates[$language->locale] = [
                    'fullname' => fake()->lastName() . ' ' . fake()->firstName(),
                    'profession' => fake()->jobTitle(),
                    'comment' => fake()->paragraph(),
                ];
            }

            Testimonial::query()->create([
                'translates' => $translates,
                'photo_path' => 'photo-' . rand(1, 5) . '.jpg',
                'rating' => rand(3, 5),
                'is_active' => true,
            ]);
        }
    }
}
