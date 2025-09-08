<?php

namespace Database\Seeders;

use App\Models\User;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database
     */
    public function run(): void
    {
        Cache::flush();
        if (App::environment('local')) {
            $this->call(UserSeeder::class);
            $this->call(SettingSeeder::class);
            $this->call(PermissionSeeder::class);
            $this->call(TranslationSeeder::class);
            $this->call(PageSeeder::class);
            $this->call(SeoLinkSeeder::class);
            $this->call(LocationSeeder::class);
            $this->call(AttributeSeeder::class);
            $this->call(CategorySeeder::class);
            $this->call(ServiceSeeder::class);
            $this->call(MessagingSystemSeeder::class);
            $this->call(CommentSeeder::class);
            $this->call(ComplaintSeeder::class);
            $this->call(ClinicSeeder::class);
            $this->call(DoctorSeeder::class);
            $this->call(PatientSeeder::class);
            $this->call(AppointmentSeeder::class);
            $this->call(ReviewSeeder::class);
            $this->call(NotificationSeeder::class);
            $this->call(PaymentSeeder::class);
            $this->call(FaqSeeder::class);
            $this->call(BlogSeeder::class);
            $this->call(SliderSeeder::class);
        }
    }
}
