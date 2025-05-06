<?php

namespace Database\Factories;

use App\Enums\GenderEnum;
use App\Enums\UserStatusEnum;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->firstName(),
            'surname' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => bcrypt('123'), // Bütün fake istifadəçilər üçün eyni şifrə
            'phone' => $this->faker->phoneNumber(),
            'photo_path' => 'photo-'. rand(1, 5) . '.jpg',
            'status' => UserStatusEnum::getRandomValue(),
            'role_id' => 1,
            'gender' => GenderEnum::getRandomValue(),
            'remember_token' => Str::random(10),
        ];
    }
}
