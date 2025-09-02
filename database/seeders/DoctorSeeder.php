<?php

namespace Database\Seeders;

use App\Enums\GenderEnum;
use App\Enums\UserStatusEnum;
use App\Enums\UserTypeEnum;
use App\Models\Category;
use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\DoctorAttributeValue;
use App\Models\DoctorCertificate;
use App\Models\DoctorClinic;
use App\Models\DoctorClinicService;
use App\Models\DoctorEducation;
use App\Models\DoctorExperience;
use App\Models\DoctorLanguage;
use App\Models\DoctorSchedule;
use App\Models\DoctorUnavailability;
use App\Models\Service;
use App\Models\User;
use App\Models\UserPreference;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DoctorSeeder extends Seeder
{
    private array $universities = [];
    private array $workplaces = [];
    private array $certificateTypes = [];
    private array $languages = [];
    private array $doctorNames = [];
    private array $socialPlatforms = [];

    /**
     * @throws \Throwable
     */
    public function run(): void
    {
        try {
            $this->clearExistingDoctorData();
            $this->prepareData();
            $this->createDoctors(50);
        } catch (\Exception $e) {
            $this->command->error('DoctorSeeder xətası: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Həkim modulu ilə əlaqəli bütün məlumatları silir
     */
    private function clearExistingDoctorData(): void
    {
        $this->command->info('Mövcud həkim məlumatları silinir...');

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        try {
            $tablesToClear = [
                'doctor_attribute_values',
                'doctor_schedules',
                'doctor_unavailability',
                'doctor_clinic_services',
                'doctor_clinic',
                'doctor_languages',
                'doctor_certificates',
                'doctor_experience',
                'doctor_education',
                'doctors'
            ];

            foreach ($tablesToClear as $table) {
                if ($this->tableExists($table)) {
                    DB::table($table)->truncate();
                    $this->command->info("✓ {$table} cədvəli təmizləndi");
                } else {
                    $this->command->warn("⚠ {$table} cədvəli mövcud deyil - atlandı");
                }
            }

            if ($this->tableExists('users') && $this->tableExists('user_preferences')) {
                $doctorUserIds = DB::table('users')
                    ->where('user_type', UserTypeEnum::Doctor)
                    ->pluck('id');

                if ($doctorUserIds->isNotEmpty()) {
                    DB::table('user_preferences')
                        ->whereIn('user_id', $doctorUserIds)
                        ->delete();

                    DB::table('users')
                        ->where('user_type', UserTypeEnum::Doctor)
                        ->delete();

                    $this->command->info("✓ Həkim istifadəçiləri silindi ({$doctorUserIds->count()} ədəd)");
                }
            }

            $this->command->info('✅ Mövcud həkim məlumatları uğurla silindi.');
        } catch (\Exception $e) {
            $this->command->error('❌ Məlumat silinərkən xəta: ' . $e->getMessage());
            throw $e;
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }
    }

    private function tableExists(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function addAttributesToDoctor(Doctor $doctor): void
    {
        if (!$doctor->category || !method_exists($doctor->category, 'attributes')) {
            return;
        }

        $categoryAttributes = $doctor->category->attributes;

        if ($categoryAttributes->isEmpty()) {
            return;
        }

        $randomAttributes = $categoryAttributes->shuffle()->take(rand(3, min(5, $categoryAttributes->count())));

        foreach ($randomAttributes as $categoryAttribute) {
            $attribute = $categoryAttribute->attribute;
            if (!$attribute) {
                continue;
            }

            $exists = DoctorAttributeValue::where('doctor_id', $doctor->id)
                ->where('attribute_id', $attribute->id)
                ->exists();

            if ($exists) {
                continue;
            }

            $data = [
                'doctor_id' => $doctor->id,
                'attribute_id' => $attribute->id,
                'value' => $this->generateAttributeValue($attribute),
            ];

            $attributeOption = $attribute?->options()->inRandomOrder()->first();
            if ($attributeOption) {
                $data['attribute_option_id'] = $attributeOption->id;
            }

            DoctorAttributeValue::create($data);
        }
    }

    private function generateAttributeValue($attribute): string
    {
        $attributeTypes = ['text', 'number', 'boolean', 'select', 'multiselect'];
        $type = fake()->randomElement($attributeTypes);

        return match ($type) {
            'text' => fake()->sentence(3),
            'number' => (string)fake()->numberBetween(1, 100),
            'boolean' => fake()->boolean() ? 'true' : 'false',
            'select' => fake()->randomElement(['Option 1', 'Option 2', 'Option 3']),
            'multiselect' => implode(',', fake()->randomElements(['Tag 1', 'Tag 2', 'Tag 3'], rand(1, 2))),
            default => fake()->word(),
        };
    }

    private function prepareData(): void
    {
        $this->universities = [
            'Azərbaycan Tibb Universiteti',
            'Azərbaycan Dövlət Həkimləri Təkmilləşdirmə İnstitutu',
            'İstanbul Universiteti Tibb Fakültəsi',
            'Ankara Universiteti Tibb Fakültəsi',
            'Moskva Dövlət Tibb Universiteti',
            'Bakı Slavyan Universiteti',
            'Xəzər Universiteti',
            'Moskva Həkimlik Akademiyası',
            'Tbilisi Dövlət Tibb Universiteti',
            'Kiev Tibb Universiteti'
        ];

        $this->workplaces = [
            'Respublika Klinik Xəstəxanası',
            'Mərkəzi Klinik Xəstəxana',
            'Neftçilər Xəstəxanası',
            'Akademik Mir-Qasımov adına Respublika Klinik Xəstəxanası',
            'Bakı Şəhər Klinik Xəstəxanası',
            'Özəl Atlas Medical Center',
            'Avicenna Klinikası',
            'Bona Dea Klinikası',
            'Medical Plaza',
            'Yeni Klinik'
        ];

        $this->certificateTypes = [
            'Tibb İxtisas Diplomu',
            'Residency Sertifikatı',
            'Avropa Kardioloji Cəmiyyəti Sertifikatı',
            'American Heart Association Sertifikatı',
            'İlk Tibbi Yardım Sertifikatı',
            'Laparoskopik Cərrahiyyə Sertifikatı',
            'Diaqnostik Görüntüləmə Sertifikatı',
            'Pediatrik Intensiv Terapiya Sertifikatı'
        ];

        $this->languages = [
            ['language' => 'Azərbaycan dili', 'proficiency' => 'native'],
            ['language' => 'Türk dili', 'proficiency' => 'fluent'],
            ['language' => 'İngilis dili', 'proficiency' => 'intermediate'],
            ['language' => 'Rus dili', 'proficiency' => 'fluent'],
            ['language' => 'Ərəb dili', 'proficiency' => 'basic'],
            ['language' => 'Fars dili', 'proficiency' => 'basic'],
        ];

        $this->socialPlatforms = [
            'facebook' => 'https://facebook.com/',
            'instagram' => 'https://instagram.com/',
            'linkedin' => 'https://linkedin.com/in/',
            'twitter' => 'https://twitter.com/',
            'youtube' => 'https://youtube.com/c/',
            'telegram' => 'https://t.me/',
        ];

        $this->doctorNames = [
            ['name' => 'Rəşad', 'surname' => 'Məmmədov', 'title' => 'Dr.'],
            ['name' => 'Aysel', 'surname' => 'Həsənova', 'title' => 'Dr.'],
            ['name' => 'Elvin', 'surname' => 'Quliyev', 'title' => 'Prof. Dr.'],
            ['name' => 'Səbinə', 'surname' => 'Əliyeva', 'title' => 'Dr.'],
            ['name' => 'Murad', 'surname' => 'İsmayılov', 'title' => 'Doç. Dr.'],
            ['name' => 'Nigar', 'surname' => 'Babayeva', 'title' => 'Dr.'],
            ['name' => 'Tural', 'surname' => 'Abdullayev', 'title' => 'Prof. Dr.'],
            ['name' => 'Günay', 'surname' => 'Məhərrəmova', 'title' => 'Dr.'],
            ['name' => 'Emil', 'surname' => 'Nağıyev', 'title' => 'Dr.'],
            ['name' => 'Leyla', 'surname' => 'Hüseynova', 'title' => 'Dr.'],
            ['name' => 'Kərim', 'surname' => 'Vəliyev', 'title' => 'Doç. Dr.'],
            ['name' => 'Mehriban', 'surname' => 'Rzayeva', 'title' => 'Dr.'],
            ['name' => 'Faiq', 'surname' => 'Mustafayev', 'title' => 'Prof. Dr.'],
            ['name' => 'Aynur', 'surname' => 'Əhmədova', 'title' => 'Dr.'],
            ['name' => 'İlham', 'surname' => 'Cəfərov', 'title' => 'Dr.'],
        ];
    }

    private function createDoctors(int $count): void
    {
        $categories = Category::where('is_active', true)->get();
        if ($categories->isEmpty()) {
            $this->command->info('Kateqoriya tapılmadı! Əvvəl CategorySeeder işə salın.');
            return;
        }

        $services = Service::where('is_active', true)->get();
        if ($services->isEmpty()) {
            $this->command->info('Xidmət tapılmadı! Əvvəl ServiceSeeder işə salın.');
            return;
        }

        $clinics = Clinic::where('is_active', true)->get();
        if ($clinics->isEmpty()) {
            $this->command->info('Klinika tapılmadı! Əvvəl ClinicSeeder işə salın.');
            return;
        }

        for ($i = 0; $i < $count; $i++) {
            $this->createSingleDoctor($categories, $clinics, $services, $i);
        }

        $this->command->info("✅ $count həkim uğurla yaradıldı!");
    }

    private function createSingleDoctor($categories, $clinics, $services, $index): void
    {
        $doctorInfo = collect($this->doctorNames)->random();
        $gender = fake()->randomElement([GenderEnum::Male, GenderEnum::Female]);

        $user = User::create([
            'name' => $doctorInfo['name'],
            'surname' => $doctorInfo['surname'],
            'email' => 'doctor_' . ($index + 1) . '@doctap.az',
            'password' => Hash::make('password123'),
            'username' => Str::slug($doctorInfo['name'] . '-' . $doctorInfo['surname']) . '-' . ($index + 1),
            'phone' => '+994' . fake('az_AZ')->randomNumber(9, true),
            'gender' => $gender,
            'birthdate' => fake()->dateTimeBetween('-65 years', '-25 years')->format('Y-m-d'),
            'user_type' => UserTypeEnum::Doctor,
            'status' => UserStatusEnum::Active,
            'email_verified_at' => now(),
        ]);

        UserPreference::create([
            'user_id' => $user->id,
            'language' => 'az',
            'notification_settings' => [
                'email_notifications' => true,
                'push_notifications' => true,
                'appointment_reminders' => true,
            ],
        ]);

        $category = $categories->random();
        $subCategories = $categories->where('parent_id', $category->id);
        $subCategory = $subCategories->isNotEmpty() ? $subCategories->random() : null;

        $doctor = Doctor::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'sub_category_id' => $subCategory?->id,
            'title' => $doctorInfo['title'],
            'biography' => $this->generateBiography($doctorInfo['name'], $category),
            'consultation_fee' => fake()->randomFloat(2, 20, 200),
            'consultation_duration' => fake()->randomElement([30, 45, 60]),
            'working_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            'is_verified' => fake()->boolean(80),
            'is_featured' => fake()->boolean(20),
            'years_of_experience' => fake()->numberBetween(2, 35),
            'practice_license_number' => 'LIC-' . fake()->unique()->numberBetween(100000, 999999),
            'workplace' => [
                'name' => fake()->randomElement($this->workplaces),
                'address' => fake('az_AZ')->address,
                'phone' => '+994' . fake('az_AZ')->randomNumber(9, true),
                'latitude' => fake()->latitude(40.3, 40.5),
                'longitude' => fake()->longitude(49.8, 50.0),
            ],
            'available_for_home_visit' => fake()->boolean(30),
            'available_for_online_consultation' => fake()->boolean(70),
            'home_visit_fee' => fake()->boolean(30) ? fake()->randomFloat(2, 50, 300) : null,
            'online_consultation_fee' => fake()->boolean(70) ? fake()->randomFloat(2, 15, 100) : null,
            'social_media_links' => $this->generateSocialMediaLinks($user->username),
            'average_rating' => fake()->numberBetween(300, 500),
            'total_ratings' => fake()->numberBetween(5, 200),
            'total_patients' => fake()->numberBetween(50, 2000),
        ]);

        $this->createEducation($doctor);
        $this->createExperience($doctor);
        $this->createCertificates($doctor);
        $this->createLanguages($doctor);
        $this->createClinicRelations($doctor, $clinics);

        if ($this->tableExists('doctor_clinic_services')) {
            $this->createDoctorServices($doctor, $services, $clinics);
        }

        if ($this->tableExists('doctor_schedules')) {
            $this->createSchedules($doctor, $clinics);
        }

        if ($this->tableExists('doctor_unavailability')) {
            $this->createUnavailabilities($doctor);
        }

        if ($this->tableExists('doctor_attribute_values')) {
            $this->addAttributesToDoctor($doctor);
        }
    }

    private function generateBiography($name, $category): string
    {
        $experiences = [
            "Dr. $name tibb sahəsində uzun illər təcrübəyə malikdir.",
            "Xəstələrin sağlamlığı və rifahı üçün həmişə ən yaxşısını verməyə çalışır.",
            "Müasir tibb texnologiyalarından istifadə edərək keyfiyyətli xidmət göstərir.",
            "Peşəkar yanaşma və diqqətli münasibət ilə xəstələrə yardım edir.",
        ];

        return implode(' ', fake()->randomElements($experiences, fake()->numberBetween(2, 4)));
    }

    private function generateSocialMediaLinks($username): ?array
    {
        if (!fake()->boolean(60)) {
            return null;
        }

        $socialLinks = [];
        $platformCount = fake()->numberBetween(1, 3);
        $selectedPlatforms = fake()->randomElements(array_keys($this->socialPlatforms), $platformCount);

        foreach ($selectedPlatforms as $platform) {
            $socialLinks[$platform] = $this->socialPlatforms[$platform] . 'dr.' . $username;
        }

        return $socialLinks;
    }

    private function createEducation(Doctor $doctor): void
    {
        if (!$this->tableExists('doctor_educations')) {
            return;
        }

        $educationCount = fake()->numberBetween(1, 3);

        for ($i = 0; $i < $educationCount; $i++) {
            DoctorEducation::create([
                'doctor_id' => $doctor->id,
                'university' => fake()->randomElement($this->universities),
                'faculty' => 'Tibb Fakültəsi',
                'degree' => fake()->randomElement(['Bakalavr', 'Magistr', 'PhD', 'Rezidentura']),
                'specialization' => fake()->randomElement(['Ümumi Tibb', 'Daxili Xəstəliklər', 'Cərrahiyyə', 'Pediatriya']),
                'start_date' => fake()->dateTimeBetween('-20 years', '-10 years'),
                'end_date' => fake()->dateTimeBetween('-8 years', '-2 years'),
                'location' => fake()->randomElement(['Bakı, Azərbaycan', 'İstanbul, Türkiyə', 'Moskva, Rusiya']),
                'description' => 'Tibb sahəsində əsaslı təhsil alıb, yüksək nəticələr göstərib.',
                'is_currently_studying' => false,
            ]);
        }
    }

    private function createExperience(Doctor $doctor): void
    {
        if (!$this->tableExists('doctor_experience')) {
            return;
        }

        $experienceCount = fake()->numberBetween(2, 5);

        for ($i = 0; $i < $experienceCount; $i++) {
            $isCurrent = $i === 0 && fake()->boolean(70);

            DoctorExperience::create([
                'doctor_id' => $doctor->id,
                'workplace' => fake()->randomElement($this->workplaces),
                'position' => fake()->randomElement(['Həkim', 'Baş həkim', 'Şöbə müdiri', 'Konsultant həkim']),
                'start_date' => fake()->dateTimeBetween('-15 years', '-1 year'),
                'end_date' => $isCurrent ? null : fake()->dateTimeBetween('-5 years', 'now'),
                'location' => 'Bakı, Azərbaycan',
                'description' => 'Xəstələrə keyfiyyətli tibbi xidmət göstərib, komanda ilə uğurla işləyib.',
                'is_current_job' => $isCurrent,
            ]);
        }
    }

    private function createCertificates(Doctor $doctor): void
    {
        if (!$this->tableExists('doctor_certificates')) {
            return;
        }

        $certificateCount = fake()->numberBetween(1, 4);

        for ($i = 0; $i < $certificateCount; $i++) {
            DoctorCertificate::create([
                'doctor_id' => $doctor->id,
                'name' => fake()->randomElement($this->certificateTypes),
                'issuing_organization' => fake()->randomElement([
                    'Səhiyyə Nazirliyi',
                    'Azərbaycan Həkimlər Assosiasiyası',
                    'Avropa Tibb Cəmiyyəti',
                    'Amerika Tibb Assosiasiyası'
                ]),
                'issue_date' => fake()->dateTimeBetween('-10 years', '-1 year'),
                'expiry_date' => fake()->boolean(60) ? fake()->dateTimeBetween('now', '+5 years') : null,
                'description' => 'Peşəkar səriştənin təsdiqi üçün alınmış sertifikat.',
                'is_verified' => fake()->boolean(90),
            ]);
        }
    }

    private function createLanguages(Doctor $doctor): void
    {
        if (!$this->tableExists('doctor_languages')) {
            return;
        }

        $languageCount = fake()->numberBetween(2, 4);
        $selectedLanguages = fake()->randomElements($this->languages, $languageCount);

        foreach ($selectedLanguages as $lang) {
            DoctorLanguage::create([
                'doctor_id' => $doctor->id,
                'language' => $lang['language'],
                'proficiency' => $lang['proficiency'],
            ]);
        }
    }

    private function createClinicRelations(Doctor $doctor, $clinics): void
    {
        if (!$this->tableExists('doctor_clinic')) {
            return;
        }

        $clinicCount = fake()->numberBetween(1, 3);
        $selectedClinics = $clinics->random($clinicCount);

        foreach ($selectedClinics as $index => $clinic) {
            $timeRand = rand(0, 2);
            $customClinic = rand(1, 4);

            if ($customClinic === 2) {
                DoctorClinic::query()->create([
                    'doctor_id' => $doctor->id,
                    'custom_clinic' => [
                        'name' => fake()->company . ' Klinikası',
                        'latitude' => fake()->latitude(40.3, 40.5),
                        'longitude' => fake()->longitude(49.8, 50.0),
                    ],
                    'profession' => fake()->randomElement(['Həkim', 'Baş həkim', 'Şöbə müdiri', 'Konsultant həkim']),
                    'is_main_workplace' => $index === 0 ? 1 : 0,
                    'is_active' => fake()->boolean(90) ? 1 : 0,
                    'note' => fake()->boolean(30) ? 'Həkim bu klinikada ' . fake()->numberBetween(1, 5) . ' il işləyib' : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                DoctorClinic::query()->create([
                    'doctor_id' => $doctor->id,
                    'clinic_id' => $clinic->id,
                    'work_time' => [
                        'start_day' => rand(1, 3),
                        'start_time' => ['09:00', '10:00', '11:00'][$timeRand],
                        'end_day' => rand(4, 7),
                        'end_time' => ['18:00', '19:00', '20:00'][$timeRand],
                    ],
                    'profession' => fake()->randomElement(['Həkim', 'Baş həkim', 'Şöbə müdiri', 'Konsultant həkim']),
                    'is_main_workplace' => $index === 0 ? 1 : 0,
                    'is_active' => fake()->boolean(90) ? 1 : 0,
                    'note' => fake()->boolean(30) ? 'Həkim bu klinikada ' . fake()->numberBetween(1, 5) . ' il işləyib' : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    private function createDoctorServices(Doctor $doctor, $services, $clinics): void
    {
        // DÜZƏLİŞ: doctor->category_id ilə filtre
        $categoryServices = $services->where('category_id', $doctor->category_id);

        if ($categoryServices->isEmpty()) {
            $categoryServices = $services->random(fake()->numberBetween(3, 8));
        } else {
            $categoryServices = $categoryServices->random(min($categoryServices->count(), fake()->numberBetween(3, 8)));
        }

        $doctorClinicIds = DB::table('doctor_clinic')
            ->where('doctor_id', $doctor->id)
            ->where('is_active', 1)
            ->pluck('id');

        if ($doctorClinicIds->isEmpty()) {
            return;
        }

        foreach ($categoryServices as $service) {
            foreach ($doctorClinicIds as $clinicId) {
                DoctorClinicService::create([
                    'doctor_clinic_id' => $clinicId,
                    'service_id' => $service->id,
                    'price' => fake()->randomFloat(2, max(10, ($service->price ?? 50) * 0.8), ($service->price ?? 100) * 1.2),
                    'duration' => fake()->randomElement([30, 45, 60, 90]),
                    'description' => fake()->boolean(40) ? 'Həkimin bu xidmət üçün əlavə qeydi' : null,
                    'is_active' => fake()->boolean(95),
                ]);
            }
        }
    }

    /**
     * DÜZƏLİŞ: Recurring availability strukturu ilə schedule yarat
     */
    private function createSchedules(Doctor $doctor, $clinics): void
    {
        // Aktiv klinikalar (clinic_id lazımdır)
        $clinicIds = DB::table('doctor_clinic')
            ->where('doctor_id', $doctor->id)
            ->whereNotNull('clinic_id')
            ->where('is_active', 1)
            ->pluck('clinic_id');

        if ($clinicIds->isEmpty()) {
            return;
        }

        // 1-2 fərqli recurring interval
        $recurringCount = fake()->numberBetween(1, 2);

        for ($i = 0; $i < $recurringCount; $i++) {
            $clinicId = $clinicIds->random();

            // Tarix aralığı: bu həftədən başlayıb ~6-10 həftəlik
            $startDate = Carbon::now()->startOfWeek()->addWeeks(fake()->numberBetween(0, 2))->toDateString();
            $endDate   = Carbon::parse($startDate)->addWeeks(fake()->numberBetween(6, 10))->toDateString();

            // Həftəlik tezlik: every 1 və ya 2
            $every     = fake()->randomElement([1, 2]);
            // Günlər: həftənin 2-4 günü
            $daysCount = fake()->numberBetween(2, 4);
            $days      = collect([1,2,3,4,5])->random($daysCount)->values()->toArray(); // 1..5 (Mon..Fri)

            // Saat aralığı
            $startHour = fake()->numberBetween(8, 11);
            $endHour   = fake()->numberBetween(max($startHour + 6, 15), 20);

            DoctorSchedule::create([
                'doctor_id'  => $doctor->id,
                'clinic_id'  => $clinicId,

                'start_date' => $startDate,
                'end_date'   => $endDate,
                'from_time'  => sprintf('%02d:00', $startHour),
                'to_time'    => sprintf('%02d:00', $endHour),

                'frequency'  => 'weekly',
                'every'      => $every,
                'days'       => $days,

                'is_active'  => true,
                'note'       => fake()->boolean(20) ? 'Recurrence schedule qeydi' : null,
            ]);
        }
    }

    /**
     * DÜZƏLİŞ: Unavailability start_time/end_time/note ilə
     */
    private function createUnavailabilities(Doctor $doctor): void
    {
        $clinicIds = DB::table('doctor_clinic')
            ->where('doctor_id', $doctor->id)
            ->where('is_active', 1)
            ->pluck('clinic_id');

        $unavailabilityCount = fake()->numberBetween(2, 6);

        for ($i = 0; $i < $unavailabilityCount; $i++) {
            $clinicId = ($clinicIds->isNotEmpty() && fake()->boolean(70)) ? $clinicIds->random() : null;

            // Keçmiş və ya gələcək interval
            $isPast = fake()->boolean(60);
            $start  = $isPast
                ? fake()->dateTimeBetween('-6 months', '-1 week')
                : fake()->dateTimeBetween('+1 week', '+3 months');

            // 1-3 gün arası
            $durationDays = fake()->numberBetween(1, 3);
            $end = (clone $start)->modify("+{$durationDays} days");

            DoctorUnavailability::create([
                'doctor_id'  => $doctor->id,
                'clinic_id'  => $clinicId,
                'start_time' => Carbon::instance($start)->format('Y-m-d H:i:s'),
                'end_time'   => Carbon::instance($end)->format('Y-m-d H:i:s'),
                'note'       => fake()->boolean(70)
                    ? fake()->randomElement([
                        'Məzuniyyət', 'Konfrans/Seminar', 'Şəxsi məşğuliyyət',
                        'Tibbi müayinə', 'Ailə mərasimi'
                    ])
                    : null,
            ]);
        }
    }

    private function generateUnavailabilityDescription($reason): ?string
    {
        $descriptions = [
            'Məzuniyyət' => [
                'Ailə ilə dincəlmək üçün planlaşdırılmış məzuniyyət',
                'İllik məzuniyyət dövrü',
                'Şəxsi istirahət üçün vaxt',
            ],
            'Konfrans/Seminar' => [
                'Peşəkar inkişaf üçün tibbi konfrans',
                'Beynəlxalq tibb simpoziumu',
                'İxtisasartırma kursları',
                'Elmi tədqiqat konfransı',
            ],
            'Şəxsi məşğuliyyət' => [
                'Ailə məsələləri ilə bağlı',
                'Şəxsi işlər',
                'Planlaşdırılmış görüş',
            ],
            'Tibbi müayinə' => [
                'Rutin tibbi yoxlama',
                'Sağlamlıq monitoring',
                'Profilaktik müayinə',
            ],
            'Ailə mərasimi' => [
                'Ailə tədbirləri',
                'Xüsusi günlər',
                'Mühüm ailə hadisələri',
            ],
        ];

        return fake()->boolean(70) ? fake()->randomElement($descriptions[$reason] ?? ['Əlavə məlumat yoxdur']) : null;
    }
}
