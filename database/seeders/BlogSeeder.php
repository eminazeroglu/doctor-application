<?php

namespace Database\Seeders;

use App\Helpers\Helper;
use App\Models\Blog;
use App\Models\Category;
use Illuminate\Database\Seeder;

class BlogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Blog::query()->truncate();
        $faker = \Faker\Factory::create();
        for ($i = 0; $i < 60; $i++) {
            $name = $faker->sentence(3, true);
            Blog::create([
                'translates' => [
                    'az' => [
                        'title' => $name,
                        'description' => $faker->paragraph(3, true),
                        'content' => $faker->paragraph(10, true),
                    ],
                    'en' => [
                        'title' => $faker->sentence(3, true),
                        'description' => $faker->paragraph(3, true),
                        'content' => $faker->paragraph(10, true),
                    ],
                    'ru' => [
                        'title' => $faker->sentence(3, true),
                        'description' => $faker->paragraph(3, true),
                        'content' => $faker->paragraph(10, true),
                    ]
                ],
                'slug' => Helper::createSlug(Blog::class, $name),
                'category_id' => Category::query()->inRandomOrder()->first()->id,
                'photo_path' => 'photo-' . rand(1, 5) . '.jpg',
            ]);
        }
    }
}
