<?php

namespace Database\Seeders;

use App\Enums\ActivityLogActionEnum;
use App\Enums\ActivityLogStatusEnum;
use App\Models\ActivityLog;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Faker\Factory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ActivityLogSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Factory::create();

        // 1. KÖHNƏ LOGLARI TAM SİL
        Schema::disableForeignKeyConstraints();
        DB::table('activity_logs')->truncate();

        DB::beginTransaction();

        try {
            // 2. İSTİFADƏÇİLƏR ÜZRƏ LOGLAR -------------------------------------------------
            $allUsers = User::query()->get();
            $users = User::factory()
                ->count(25)
                ->create()
                ->each(function ($user) use ($faker, $allUsers) {
                    // 3 dəfə REAL dəyişiklik (email + phone)
                    foreach (range(1, 3) as $i) {
                        $oldData = $user->getAttributes(); // Köhnə dəyərləri saxla
                        $user->update([
                            'email' => $faker->unique()->safeEmail,
                            'phone' => $faker->unique()->phoneNumber
                        ]);
                        $newData = $user->getChanges(); // Yeni dəyərləri saxla

                        // Manual olaraq log yaz
                        ActivityLog::create([
                            'model_type' => User::class,
                            'model_id' => $user->id,
                            'action' => ActivityLogActionEnum::UPDATED,
                            'old_data' => $oldData,
                            'new_data' => $newData,
                            'ip_address' => $faker->ipv4,
                            'user_agent' => $faker->userAgent,
                            'method' => $faker->randomElement(['GET', 'POST', 'PUT', 'DELETE']),
                            'url' => $faker->url,
                            'status' => ActivityLogStatusEnum::SUCCESS,
                            'created_by' => $allUsers->random()->id,
                            'created_at' => Carbon::now(),
                            'updated_at' => Carbon::now(),
                            'meta_data' => [
                                'browser' => $faker->randomElement(['Chrome', 'Firefox', 'Safari', 'Edge']),
                                'platform' => $faker->randomElement(['Windows', 'macOS', 'Linux', 'Android', 'iOS']),
                                'device' => $faker->randomElement(['Desktop', 'Mobile', 'Tablet']),
                            ],
                        ]);
                    }

                    // Silinmə logu üçün
                    $user->delete();
                    ActivityLog::create([
                        'model_type' => User::class,
                        'model_id' => $user->id,
                        'action' => ActivityLogActionEnum::DELETED,
                        'ip_address' => $faker->ipv4,
                        'user_agent' => $faker->userAgent,
                        'method' => $faker->randomElement(['GET', 'POST', 'PUT', 'DELETE']),
                        'url' => $faker->url,
                        'status' => ActivityLogStatusEnum::SUCCESS,
                        'created_by' => $allUsers->random()->id,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                        'meta_data' => [
                            'browser' => $faker->randomElement(['Chrome', 'Firefox', 'Safari', 'Edge']),
                            'platform' => $faker->randomElement(['Windows', 'macOS', 'Linux', 'Android', 'iOS']),
                            'device' => $faker->randomElement(['Desktop', 'Mobile', 'Tablet']),
                        ],
                    ]);
                });

            // 3. SETTINGS ÜZRƏ LOGLAR --------------------------------------------------------
            foreach (range(1, 15) as $i) {
                $setting = Setting::create(['key' => Str::slug($faker->words(2, true)), 'values' => ['v' => 1]]);

                // 2 dəfə update et
                $oldValues = $setting->values;
                $setting->update(['values' => ['v' => 2]]);
                $newValues = $setting->values;

                ActivityLog::create([
                    'model_type' => Setting::class,
                    'model_id' => $setting->id,
                    'action' => ActivityLogActionEnum::UPDATED,
                    'old_data' => $oldValues,
                    'new_data' => $newValues,
                    'ip_address' => $faker->ipv4,
                    'user_agent' => $faker->userAgent,
                    'method' => $faker->randomElement(['GET', 'POST', 'PUT', 'DELETE']),
                    'url' => $faker->url,
                    'status' => ActivityLogStatusEnum::SUCCESS,
                    'created_by' => $allUsers->random()->id,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                    'meta_data' => [
                        'browser' => $faker->randomElement(['Chrome', 'Firefox', 'Safari', 'Edge']),
                        'platform' => $faker->randomElement(['Windows', 'macOS', 'Linux', 'Android', 'iOS']),
                        'device' => $faker->randomElement(['Desktop', 'Mobile', 'Tablet']),
                    ],
                ]);

                // Manual view logu
                ActivityLog::create([
                    'model_type' => Setting::class,
                    'model_id' => $setting->id,
                    'action' => ActivityLogActionEnum::VIEWED,
                    'ip_address' => $faker->ipv4,
                    'user_agent' => $faker->userAgent,
                    'method' => $faker->randomElement(['GET', 'POST', 'PUT', 'DELETE']),
                    'url' => $faker->url,
                    'status' => ActivityLogStatusEnum::SUCCESS,
                    'created_by' => $allUsers->random()->id,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                    'meta_data' => [
                        'viewed_by' => $allUsers->random()->id,
                        'view_duration' => rand(5, 120) . 's',
                        'browser' => $faker->randomElement(['Chrome', 'Firefox', 'Safari', 'Edge']),
                        'platform' => $faker->randomElement(['Windows', 'macOS', 'Linux', 'Android', 'iOS']),
                        'device' => $faker->randomElement(['Desktop', 'Mobile', 'Tablet']),
                    ],
                ]);
            }

            // 4. MANUAL ƏMƏLİYYATLAR ---------------------------------------------------------
            $users->each(function ($user) use ($faker, $allUsers) {
                // Login/Logout (hər user üçün 3-5 dəfə)
                foreach (range(1, rand(3, 5)) as $i) {
                    ActivityLog::create([
                        'model_type' => User::class,
                        'model_id' => $user->id,
                        'action' => ActivityLogActionEnum::LOGIN,
                        'ip_address' => $faker->ipv4,
                        'user_agent' => $faker->userAgent,
                        'method' => $faker->randomElement(['GET', 'POST', 'PUT', 'DELETE']),
                        'url' => $faker->url,
                        'status' => ActivityLogStatusEnum::SUCCESS,
                        'created_by' => $allUsers->random()->id,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                        'meta_data' => [
                            'device_id' => Str::uuid(),
                            'browser' => $faker->randomElement(['Chrome', 'Firefox', 'Safari', 'Edge']),
                            'platform' => $faker->randomElement(['Windows', 'macOS', 'Linux', 'Android', 'iOS']),
                            'device' => $faker->randomElement(['Desktop', 'Mobile', 'Tablet']),
                        ],
                    ]);

                    ActivityLog::create([
                        'model_type' => User::class,
                        'model_id' => $user->id,
                        'action' => ActivityLogActionEnum::LOGOUT,
                        'ip_address' => $faker->ipv4,
                        'user_agent' => $faker->userAgent,
                        'method' => $faker->randomElement(['GET', 'POST', 'PUT', 'DELETE']),
                        'url' => $faker->url,
                        'status' => ActivityLogStatusEnum::SUCCESS,
                        'created_by' => $allUsers->random()->id,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                        'meta_data' => [
                            'session_duration' => rand(60, 3600) . 's',
                            'browser' => $faker->randomElement(['Chrome', 'Firefox', 'Safari', 'Edge']),
                            'platform' => $faker->randomElement(['Windows', 'macOS', 'Linux', 'Android', 'iOS']),
                            'device' => $faker->randomElement(['Desktop', 'Mobile', 'Tablet']),
                        ],
                    ]);
                }

                // Xəta logları (40% şansla)
                if (rand(1, 100) <= 40) {
                    ActivityLog::create([
                        'model_type' => User::class,
                        'model_id' => $user->id,
                        'action' => 'server_error',
                        'ip_address' => $faker->ipv4,
                        'user_agent' => $faker->userAgent,
                        'method' => $faker->randomElement(['GET', 'POST', 'PUT', 'DELETE']),
                        'url' => $faker->url,
                        'status' => ActivityLogStatusEnum::ERROR,
                        'error_message' => $faker->sentence,
                        'created_by' => $allUsers->random()->id,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                        'meta_data' => [
                            'browser' => $faker->randomElement(['Chrome', 'Firefox', 'Safari', 'Edge']),
                            'platform' => $faker->randomElement(['Windows', 'macOS', 'Linux', 'Android', 'iOS']),
                            'device' => $faker->randomElement(['Desktop', 'Mobile', 'Tablet']),
                        ],
                    ]);
                }
            });

            // 5. TARİX MƏLUMATLARINI DÜZƏLT --------------------------------------------------
            ActivityLog::all()->each(function ($log) use ($faker) {
                $log->update([
                    'created_at' => $faker->dateTimeBetween('-6 months', 'now')
                ]);
            });

            DB::commit();

            $this->command->info('✅ 2500+ log uğurla yaradıldı!');
            $this->showAdvancedStatistics();

            Schema::enableForeignKeyConstraints();

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('❌ Xəta: ' . $e->getMessage());
        }
    }

    protected function showAdvancedStatistics(): void
    {
        $stats = DB::table('activity_logs')
            ->select([
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN action = "created" THEN 1 ELSE 0 END) as created'),
                DB::raw('SUM(CASE WHEN action = "updated" THEN 1 ELSE 0 END) as updated'),
                DB::raw('SUM(CASE WHEN action = "deleted" THEN 1 ELSE 0 END) as deleted'),
                DB::raw('SUM(CASE WHEN status = "error" THEN 1 ELSE 0 END) as errors'),
            ])
            ->first();

        $this->command->table(
            ['Ümumi', 'Yaradılma', 'Yenilənmə', 'Silinmə', 'Xətalar'],
            [[
                $stats->total,
                $stats->created,
                $stats->updated,
                $stats->deleted,
                $stats->errors
            ]]
        );
    }
}
