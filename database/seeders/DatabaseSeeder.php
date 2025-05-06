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
        $this->call(UserSeeder::class);
        $this->call(SettingSeeder::class);
        $this->call(PermissionSeeder::class);
        $this->call(TranslationSeeder::class);
        //if (App::environment('local')) {
        $this->call(PageSeeder::class);
        $this->call(SeoLinkSeeder::class);
        $this->call(PaymentServiceSeeder::class);
        $this->call(LocationSeeder::class);
        $this->call(CategorySeeder::class);
        $this->call(MessagingSystemSeeder::class);
        $this->call(CommentSeeder::class);
        $this->call(PaymentSeeder::class);
        $this->call(ComplaintSeeder::class);
        //}
    }
}
