<?php

namespace Database\Seeders;

use App\Enums\UserStatusEnum;
use App\Models\User;
use App\Models\UserLoginHistory;
use App\Models\UserPreference;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UserSeeder extends Seeder
{
    public function run()
    {
        Schema::disableForeignKeyConstraints();
        DB::table('users')->truncate();

        // Admin istifadəçi yaratmaq
        User::factory()->create([
            'name' => 'Admin',
            'surname' => 'User',
            'email' => 'admin@app.com',
            'role_id' => 2,
            'password' => bcrypt('secret'),
            'status' => UserStatusEnum::Active,
            'is_system' => true,
        ]);
        User::factory()->create([
            'name' => 'Support',
            'surname' => 'User',
            'role_id' => 2,
            'email' => 'support@app.com',
            'password' => bcrypt('secret'),
            'status' => UserStatusEnum::Active,
            'is_system' => true,
        ]);

        User::factory()->create([
            'name' => 'John',
            'surname' => 'Doe',
            'email' => 'user@example.com',
            'password' => bcrypt('password123'),
            'status' => UserStatusEnum::Active,
            'is_system' => true,
        ]);

        // 50 adi istifadəçi yaratmaq
       // User::factory()->count(50)->create();

        $this->createLoginHistory();
        $this->createPreferences();

        Schema::enableForeignKeyConstraints();
    }


    public function createLoginHistory (): void
    {
        DB::table('user_login_history')->truncate();

        $users = User::all();
        $deviceTypes = ['desktop', 'mobile', 'tablet'];
        $userAgents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'Mozilla/5.0 (iPhone; CPU iPhone OS 14_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0 Mobile/15E148 Safari/604.1',
            'Mozilla/5.0 (iPad; CPU OS 14_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0 Mobile/15E148 Safari/604.1',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.1.1 Safari/605.1.15',
        ];
        $locations = ['Bakı, Azerbaijan', 'Istanbul, Turkey', 'Moscow, Russia', 'Berlin, Germany', 'London, UK', 'New York, USA'];
        $ipAddresses = ['192.168.1.1', '10.0.0.1', '172.16.0.1', '127.0.0.1', '8.8.8.8', '1.1.1.1'];

        // Son 30 gün üçün login tarixçələri yaradaq
        foreach ($users as $user) {
            // Hər istifadəçi üçün təsadüfi sayda giriş (1-20 arası)
            $loginCount = rand(1, 20);

            for ($i = 0; $i < $loginCount; $i++) {
                // Son 30 gün ərzində təsadüfi bir tarix
                $daysAgo = rand(0, 30);
                $hoursAgo = rand(0, 23);
                $minutesAgo = rand(0, 59);

                $loggedInAt = Carbon::now()->subDays($daysAgo)->subHours($hoursAgo)->subMinutes($minutesAgo);

                // Bəzən çıxış vaxtı da əlavə edək
                $loggedOutAt = null;
                if (rand(0, 1)) {
                    $sessionDuration = rand(5, 180);
                    $loggedOutAt = (clone $loggedInAt)->addMinutes($sessionDuration);
                }

                UserLoginHistory::create([
                    'user_id' => $user->id,
                    'ip_address' => $ipAddresses[array_rand($ipAddresses)],
                    'user_agent' => $userAgents[array_rand($userAgents)],
                    'location' => $locations[array_rand($locations)],
                    'device_type' => $deviceTypes[array_rand($deviceTypes)],
                    'logged_in_at' => $loggedInAt,
                    'logged_out_at' => $loggedOutAt,
                ]);
            }
        }
    }

    public function createPreferences (): void
    {
        DB::table('user_preferences')->truncate();

        $languages = ['az', 'en', 'ru', 'tr'];
        $emailFrequencies = ['daily', 'weekly', 'monthly', 'never'];
        $timezones = ['UTC', 'Europe/Baku', 'Europe/Istanbul', 'Europe/Moscow', 'Europe/Berlin', 'America/New_York'];

        $users = User::all();

        foreach ($users as $user) {
            // Əsas ayarlar
            $darkMode = rand(0, 1) == 1;
            $language = $languages[array_rand($languages)];
            $emailFrequency = $emailFrequencies[array_rand($emailFrequencies)];
            $timezone = $timezones[array_rand($timezones)];

            // Bildiriş ayarları
            $notificationSettings = [
                'email_notifications' => rand(0, 1) == 1,
                'push_notifications' => rand(0, 1) == 1,
                'new_article_alert' => rand(0, 1) == 1,
                'comment_replies' => rand(0, 1) == 1,
                'marketing_emails' => rand(0, 1) == 1,
            ];

            // Məzmun üstünlükləri
            $contentPreferences = [
                'favorite_categories' => array_slice(['technology', 'science', 'politics', 'sports', 'business', 'entertainment'], 0, rand(1, 3)),
                'excluded_topics' => array_slice(['celebrity', 'fashion', 'gaming', 'music'], 0, rand(0, 2)),
            ];

            // Məxfilik ayarları
            $privacySettings = [
                'profile_visibility' => ['public', 'friends', 'private'][array_rand(['public', 'friends', 'private'])],
                'show_online_status' => rand(0, 1) == 1,
                'allow_messages_from' => ['everyone', 'followers', 'friends'][array_rand(['everyone', 'followers', 'friends'])],
            ];

            UserPreference::create([
                'user_id' => $user->id,
                'dark_mode' => $darkMode,
                'language' => $language,
                'notification_settings' => $notificationSettings,
                'content_preferences' => $contentPreferences,
                'timezone' => $timezone,
                'email_frequency' => $emailFrequency,
                'show_email' => rand(0, 1) == 1,
                'show_profile_views' => rand(0, 1) == 1,
                'privacy_settings' => $privacySettings,
            ]);
        }
    }
}
