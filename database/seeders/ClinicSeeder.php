<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Clinic;
use App\Models\ClinicCategory;
use App\Models\ClinicHoliday;
use App\Models\ClinicService;
use App\Models\ClinicWorkingHour;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ClinicSeeder extends Seeder
{
    /**
     * Klinika modulu üçün məlumatları seed edir.
     */
    public function run(): void
    {
        // Təhlükəsizlik üçün FK məhdudiyyətlərini müvəqqəti deaktiv edirik
        Schema::disableForeignKeyConstraints();

        // Əlaqəli cədvəlləri təmizləyirik
        DB::table('clinic_holidays')->truncate();
        DB::table('clinic_services')->truncate();
        DB::table('clinic_categories')->truncate();
        DB::table('clinic_working_hours')->truncate();
        DB::table('clinics')->truncate();

        Schema::enableForeignKeyConstraints();

        // Faker instance yaradırıq - Azərbaycan dili ilə
        $faker = \Faker\Factory::create('az_AZ');

        // Admin və ya sistem istifadəçisini götürürük
        $adminUser = User::where('is_system', true)->first();
        $createdBy = $adminUser ? $adminUser->id : null;

        // Klinika imkanları (facilities) üçün seçimlər
        $facilities = [
            'Lift',
            'Təcili yardım',
            'Pulsuz Wi-Fi',
            'Laboratoriya',
            'Rentgen',
            'USM',
            'KT',
            'MRT',
            'Parkinq',
            'Aptek',
            'Kafe',
            'Uşaq otağı',
            'Əlillik girişi'
        ];

        // Həftənin günləri
        $weekDays = [
            'Monday' => 'Bazar ertəsi',
            'Tuesday' => 'Çərşənbə axşamı',
            'Wednesday' => 'Çərşənbə',
            'Thursday' => 'Cümə axşamı',
            'Friday' => 'Cümə',
            'Saturday' => 'Şənbə',
            'Sunday' => 'Bazar'
        ];

        $this->command->info('Klinikalar yaradılır...');

        // Mövcud kateqoriyaları alırıq
        $categories = Category::all();

        // Mövcud xidmətləri alırıq
        $services = Service::all();

        // Verilənlər bazasında xidmətlər olmadığını yoxlayırıq
        if ($services->isEmpty()) {
            $this->command->warn('Diqqət: Verilənlər bazasında xidmətlər tapılmadı. İlk öncə ServiceSeeder işlətməlisiniz.');
            $this->command->info('Yenə də klinikalar yaradılır, lakin xidmətlər olmayacaq.');
        }

        // 10 klinika yaradırıq
        $clinics = [];
        for ($i = 1; $i <= 10; $i++) {
            $name = $faker->company . ' Klinikası';
            $slug = Str::slug($name);

            // Təsadüfi imkanlar seçirik
            $clinicFacilities = $faker->randomElements($facilities, $faker->numberBetween(3, 8));

            // Şəkillər üçün dummy array yaradırıq
            $images = [];
            for ($j = 1; $j <= $faker->numberBetween(3, 6); $j++) {
                $images[] = "clinic_{$i}_image_{$j}.jpg";
            }

            // İş saatları üçün json formatında məlumatlar
            $workingHours = [
                'Monday' => ['open' => '09:00', 'close' => '18:00', 'is_closed' => false],
                'Tuesday' => ['open' => '09:00', 'close' => '18:00', 'is_closed' => false],
                'Wednesday' => ['open' => '09:00', 'close' => '18:00', 'is_closed' => false],
                'Thursday' => ['open' => '09:00', 'close' => '18:00', 'is_closed' => false],
                'Friday' => ['open' => '09:00', 'close' => '18:00', 'is_closed' => false],
                'Saturday' => ['open' => '10:00', 'close' => '16:00', 'is_closed' => $faker->boolean(30)],
                'Sunday' => ['open' => '10:00', 'close' => '14:00', 'is_closed' => $faker->boolean(70)],
            ];

            $clinic = Clinic::create([
                'uuid' => (string) Str::uuid(),
                'name' => $name,
                'slug' => $slug,
                'description' => $faker->paragraph(5),
                'address' => $faker->address,
                'city' => $faker->randomElement(['Bakı', 'Sumqayıt', 'Gəncə', 'Mingəçevir', 'Şəki']),
                'region' => $faker->randomElement(['Binəqədi', 'Nəsimi', 'Yasamal', 'Nərimanov', 'Xətai', 'Sabunçu', 'Suraxanı']),
                'country' => 'Azerbaijan',
                'postal_code' => $faker->postcode,
                'phone' => $faker->phoneNumber,
                'email' => $faker->safeEmail,
                'website' => $faker->boolean(70) ? 'https://www.' . $slug . '.az' : null,
                'latitude' => $faker->latitude(40.3, 40.5), // Bakı üçün təxmini koordinatlar
                'longitude' => $faker->longitude(49.8, 50.0),
                'working_hours' => json_encode($workingHours),
                'facilities' => json_encode($clinicFacilities),
                'logo_path' => 'clinic_logo_' . $i . '.png',
                'images' => json_encode($images),
                'rating' => $faker->numberBetween(0, 500),
                'ratings_count' => $faker->numberBetween(0, 100),
                'is_verified' => $faker->boolean(80),
                'is_featured' => $faker->boolean(20),
                'is_active' => $faker->boolean(90),
                'created_by' => $createdBy,
                'parent_id' => null
            ]);

            $clinics[] = $clinic;
        }

        $this->command->info('Klinika iş saatları yaradılır...');

        // Klinika iş saatlarını yaradırıq
        foreach ($clinics as $clinic) {
            foreach ($weekDays as $dayKey => $dayName) {
                $isClosed = ($dayKey === 'Sunday') ? $faker->boolean(70) : (($dayKey === 'Saturday') ? $faker->boolean(30) : false);

                // Hətta klinika bağlı olduqda belə, açılış/bağlanış saatı təyin edirik
                // Lakin 'is_closed' true olduqda bu saatlar istifadə edilmir
                $defaultOpenTime = ($dayKey === 'Saturday' || $dayKey === 'Sunday') ? '10:00' : '09:00';
                $defaultCloseTime = ($dayKey === 'Saturday' || $dayKey === 'Sunday') ? '15:00' : '18:00';

                // Açılış saatları
                $openTime = $isClosed ? $defaultOpenTime : (
                ($dayKey === 'Saturday' || $dayKey === 'Sunday') ?
                    $faker->randomElement(['10:00', '11:00']) :
                    $faker->randomElement(['08:00', '09:00', '09:30'])
                );

                // Bağlanış saatları
                $closeTime = $isClosed ? $defaultCloseTime : (
                ($dayKey === 'Saturday' || $dayKey === 'Sunday') ?
                    $faker->randomElement(['14:00', '15:00', '16:00']) :
                    $faker->randomElement(['17:00', '18:00', '19:00', '20:00'])
                );

                ClinicWorkingHour::create([
                    'clinic_id' => $clinic->id,
                    'day_of_week' => $dayKey,
                    'open_time' => $openTime,
                    'close_time' => $closeTime,
                    'is_closed' => $isClosed,
                    'note' => $isClosed ? 'Bu gün klinika bağlıdır' : null
                ]);
            }
        }

        $this->command->info('Klinika kateqoriyaları yaradılır...');

        // Klinika kateqoriyalarını yaradırıq
        foreach ($clinics as $clinic) {
            // Hər klinika üçün 3-6 kateqoriya seçirik (mövcud kateqoriyalardan)
            if ($categories->count() > 0) {
                $selectedCategories = $faker->randomElements($categories->toArray(), min($faker->numberBetween(3, 6), $categories->count()));

                foreach ($selectedCategories as $category) {
                    ClinicCategory::create([
                        'clinic_id' => $clinic->id,
                        'category_id' => $category['id'],
                        'description' => $faker->boolean(70) ? $faker->paragraph(2) : null,
                        'is_active' => $faker->boolean(90)
                    ]);
                }
            }
        }

        $this->command->info('Klinika xidmətləri yaradılır...');

        // Klinika xidmətlərini yaradırıq
        foreach ($clinics as $clinic) {
            // Hər klinika üçün 5-8 xidmət seçirik (mövcud xidmətlərdən)
            if ($services->count() > 0) {
                // Min sayını 1 olaraq təyin edirik və services sayı ilə məhdudlaşdırırıq
                $serviceCount = min($faker->numberBetween(1, 8), $services->count());
                $selectedServices = $faker->randomElements($services->toArray(), $serviceCount);

                foreach ($selectedServices as $service) {
                    // Baza qiymətinə əsaslanaraq +/- 20% dəyişiklik edirik
                    $priceMultiplier = $faker->randomFloat(2, 0.8, 1.2);
                    $basePrice = $service['price'] ?? 100;
                    $price = round($basePrice * $priceMultiplier, 2);

                    // Baza müddətinə əsaslanaraq +/- 10 dəqiqə dəyişiklik edirik
                    $baseDuration = $service['duration'] ?? 30;
                    $duration = $baseDuration + $faker->randomElement([-10, -5, 0, 5, 10]);

                    ClinicService::create([
                        'clinic_id' => $clinic->id,
                        'service_id' => $service['id'],
                        'price' => $price,
                        'duration' => $duration,
                        'description' => $faker->boolean(60) ? $faker->paragraph(1) : null,
                        'is_active' => $faker->boolean(90)
                    ]);
                }
            }
        }

        $this->command->info('Klinika tətil günləri yaradılır...');

        // Klinika tətil günlərini yaradırıq
        $holidays = [
            '01-01' => 'Yeni il',
            '03-08' => 'Beynəlxalq Qadınlar Günü',
            '05-09' => 'Qələbə Günü',
            '05-28' => 'Respublika Günü',
            '10-18' => 'Müstəqillik Günü',
            '11-08' => 'Zəfər Günü',
            '11-12' => 'Konstitusiya Günü'
        ];

        foreach ($clinics as $clinic) {
            // Rəsmi bayramları əlavə edirik
            foreach ($holidays as $date => $name) {
                // Bu il və növbəti il üçün tarixlər
                $currentYear = date('Y');
                $nextYear = $currentYear + 1;

                ClinicHoliday::create([
                    'clinic_id' => $clinic->id,
                    'date' => $currentYear . '-' . $date,
                    'name' => $name,
                    'description' => 'Rəsmi bayram günü',
                    'is_recurring' => true
                ]);

                // Bəzi klinikalar üçün növbəti il üçün də əlavə edirik
                if ($faker->boolean(30)) {
                    ClinicHoliday::create([
                        'clinic_id' => $clinic->id,
                        'date' => $nextYear . '-' . $date,
                        'name' => $name,
                        'description' => 'Rəsmi bayram günü',
                        'is_recurring' => true
                    ]);
                }
            }

            // Xüsusi tətil günləri (təmir, korporativ tədbir və s.)
            $specialHolidaysCount = $faker->numberBetween(0, 3);
            for ($j = 0; $j < $specialHolidaysCount; $j++) {
                $holidayDate = Carbon::now()->addDays($faker->numberBetween(14, 180));

                ClinicHoliday::create([
                    'clinic_id' => $clinic->id,
                    'date' => $holidayDate->format('Y-m-d'),
                    'name' => $faker->randomElement(['Təmir günü', 'Korporativ tədbir', 'Texniki səbəblər', 'Kollektiv məzuniyyət']),
                    'description' => $faker->sentence,
                    'is_recurring' => false
                ]);
            }
        }

        $this->command->info('Klinika filialları yaradılır...');

        // Klinika filiallarını yaradırıq
        // Yalnız təsadüfi seçilmiş 3-5 klinika üçün filiallar yaradırıq
        $clinicsWithBranches = $faker->randomElements($clinics, min($faker->numberBetween(3, 5), count($clinics)));

        foreach ($clinicsWithBranches as $mainClinic) {
            // Hər klinika üçün 1-3 filial yaradırıq
            $branchCount = $faker->numberBetween(1, 3);

            for ($b = 1; $b <= $branchCount; $b++) {
                $branchName = $mainClinic->name . ' ' . $faker->randomElement(['Filialı', 'Şöbəsi', 'Mərkəzi']) . ' #' . $b;
                $branchSlug = Str::slug($branchName);

                // İş saatları üçün json formatında məlumatlar (əsas klinikadan fərqli ola bilər)
                $branchWorkingHours = [
                    'Monday' => ['open' => '09:00', 'close' => '18:00', 'is_closed' => false],
                    'Tuesday' => ['open' => '09:00', 'close' => '18:00', 'is_closed' => false],
                    'Wednesday' => ['open' => '09:00', 'close' => '18:00', 'is_closed' => false],
                    'Thursday' => ['open' => '09:00', 'close' => '18:00', 'is_closed' => false],
                    'Friday' => ['open' => '09:00', 'close' => '18:00', 'is_closed' => false],
                    'Saturday' => ['open' => '10:00', 'close' => '15:00', 'is_closed' => $faker->boolean(40)],
                    'Sunday' => ['open' => '10:00', 'close' => '14:00', 'is_closed' => $faker->boolean(80)],
                ];

                // Filial şəkilləri
                $branchImages = [];
                for ($j = 1; $j <= $faker->numberBetween(2, 4); $j++) {
                    $branchImages[] = "branch_{$mainClinic->id}_{$b}_image_{$j}.jpg";
                }

                // Yeni klinika yaradırıq (filial kimi)
                $branch = Clinic::create([
                    'uuid' => (string) Str::uuid(),
                    'name' => $branchName,
                    'slug' => $branchSlug,
                    'description' => $faker->paragraph(3),
                    'address' => $faker->address,
                    'city' => $mainClinic->city, // Eyni şəhərdə
                    'region' => $faker->randomElement(['Binəqədi', 'Nəsimi', 'Yasamal', 'Nərimanov', 'Xətai', 'Sabunçu', 'Suraxanı']),
                    'country' => 'Azerbaijan',
                    'postal_code' => $faker->postcode,
                    'phone' => $faker->phoneNumber,
                    'email' => 'filial' . $b . '@' . Str::slug($mainClinic->name) . '.az',
                    'website' => $mainClinic->website,
                    'latitude' => $faker->latitude(40.3, 40.5),
                    'longitude' => $faker->longitude(49.8, 50.0),
                    'working_hours' => json_encode($branchWorkingHours),
                    'facilities' => $mainClinic->facilities, // Eyni imkanlar
                    'logo_path' => $mainClinic->logo_path, // Eyni logo
                    'images' => json_encode($branchImages),
                    'rating' => $faker->numberBetween(0, 500),
                    'ratings_count' => $faker->numberBetween(0, 100),
                    'is_verified' => $mainClinic->is_verified,
                    'is_featured' => $faker->boolean(10),
                    'is_active' => $faker->boolean(90),
                    'created_by' => $createdBy,
                    'parent_id' => $mainClinic->id // Əsas klinika ID-si
                ]);

                // Filial üçün iş saatlarını əlavə edirik
                foreach ($weekDays as $dayKey => $dayName) {
                    $isClosed = ($dayKey === 'Sunday') ? $faker->boolean(80) : (($dayKey === 'Saturday') ? $faker->boolean(40) : false);

                    // Hətta filial bağlı olduqda belə, açılış/bağlanış saatı təyin edirik
                    // Lakin 'is_closed' true olduqda bu saatlar istifadə edilmir
                    $defaultOpenTime = ($dayKey === 'Saturday' || $dayKey === 'Sunday') ? '10:00' : '09:00';
                    $defaultCloseTime = ($dayKey === 'Saturday' || $dayKey === 'Sunday') ? '15:00' : '18:00';

                    // Açılış saatları
                    $openTime = $isClosed ? $defaultOpenTime : (
                    ($dayKey === 'Saturday' || $dayKey === 'Sunday') ?
                        $faker->randomElement(['10:00', '11:00']) :
                        $faker->randomElement(['08:00', '09:00', '09:30'])
                    );

                    // Bağlanış saatları
                    $closeTime = $isClosed ? $defaultCloseTime : (
                    ($dayKey === 'Saturday' || $dayKey === 'Sunday') ?
                        $faker->randomElement(['14:00', '15:00', '16:00']) :
                        $faker->randomElement(['17:00', '18:00', '19:00', '20:00'])
                    );

                    ClinicWorkingHour::create([
                        'clinic_id' => $branch->id,
                        'day_of_week' => $dayKey,
                        'open_time' => $openTime,
                        'close_time' => $closeTime,
                        'is_closed' => $isClosed,
                        'note' => $isClosed ? 'Bu gün filial bağlıdır' : null
                    ]);
                }

                // Filialın kateqoriyalarını əsas klinikadan götürürük
                $mainClinicCategories = ClinicCategory::where('clinic_id', $mainClinic->id)->get();
                foreach ($mainClinicCategories as $category) {
                    ClinicCategory::create([
                        'clinic_id' => $branch->id,
                        'category_id' => $category->category_id,
                        'description' => $category->description,
                        'is_active' => $faker->boolean(90)
                    ]);
                }

                // Filialın xidmətlərini əsas klinikadan götürürük (bəzilərini)
                $mainClinicServices = ClinicService::where('clinic_id', $mainClinic->id)->get();
                $mainClinicServicesCount = $mainClinicServices->count();

                // Əgər xidmət varsa, onları filial üçün də əlavə edirik
                if ($mainClinicServicesCount > 0) {
                    // 1 və ya ən çox mainClinicServicesCount qədər xidmət seçirik
                    $serviceCount = min($faker->numberBetween(1, $mainClinicServicesCount), $mainClinicServicesCount);
                    $selectedServices = $faker->randomElements($mainClinicServices->toArray(), $serviceCount);

                    foreach ($selectedServices as $service) {
                        ClinicService::create([
                            'clinic_id' => $branch->id,
                            'service_id' => $service['service_id'],
                            'price' => $service['price'] * $faker->randomFloat(2, 0.9, 1.1), // Azacıq fərqli qiymət
                            'duration' => $service['duration'],
                            'description' => $service['description'],
                            'is_active' => $faker->boolean(90)
                        ]);
                    }
                }
            }
        }

        $this->command->info('Klinika modulu üçün məlumatlar uğurla yaradıldı!');
    }
}
