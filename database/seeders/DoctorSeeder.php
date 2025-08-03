<?php

namespace Database\Seeders;

use App\Enums\GenderEnum;
use App\Enums\UserStatusEnum;
use App\Enums\UserTypeEnum;
use App\Models\Category;
use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\DoctorCertificate;
use App\Models\DoctorClinicService;
use App\Models\DoctorEducation;
use App\Models\DoctorExperience;
use App\Models\DoctorLanguage;
use App\Models\DoctorSchedule;
use App\Models\DoctorUnavailability;
use App\Models\Service;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DoctorSeeder extends Seeder
{
    private array $universities = [];
    private array $workplaces = [];
    private array $certificateTypes = [];
    private array $languages = [];
    private array $doctorNames = [];
    private array $socialPlatforms = [];

    public function run(): void
    {
        DB::transaction(function () {
            // 1. Əsas məlumatları hazırlayırıq
            $this->prepareData();

            // 2. Həkimlər yaradırıq
            $this->createDoctors(50); // 50 həkim yaradacağıq
        });
    }

    private function prepareData(): void
    {
        // Universitetlər
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

        // İş yerləri
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

        // Sertifikat növləri
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

        // Dillər
        $this->languages = [
            ['language' => 'Azərbaycan dili', 'proficiency' => 'native'],
            ['language' => 'Türk dili', 'proficiency' => 'fluent'],
            ['language' => 'İngilis dili', 'proficiency' => 'intermediate'],
            ['language' => 'Rus dili', 'proficiency' => 'fluent'],
            ['language' => 'Ərəb dili', 'proficiency' => 'basic'],
            ['language' => 'Fars dili', 'proficiency' => 'basic'],
        ];

        // Sosial media platformları
        $this->socialPlatforms = [
            'facebook' => 'https://facebook.com/',
            'instagram' => 'https://instagram.com/',
            'linkedin' => 'https://linkedin.com/in/',
            'twitter' => 'https://twitter.com/',
            'youtube' => 'https://youtube.com/c/',
            'telegram' => 'https://t.me/',
        ];

        // Həkim adları
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
        // Mövcud kateqoriyaları alırıq
        $categories = Category::where('is_active', true)->get();
        if ($categories->isEmpty()) {
            $this->command->info('Kateqoriya tapılmadı! Əvvəl CategorySeeder işə salın.');
            return;
        }

        // Mövcud xidmətləri alırıq
        $services = Service::where('is_active', true)->get();
        if ($services->isEmpty()) {
            $this->command->info('Xidmət tapılmadı! Əvvəl ServiceSeeder işə salın.');
            return;
        }

        // Mövcud klinikları alırıq
        $clinics = Clinic::where('is_active', true)->get();
        if ($clinics->isEmpty()) {
            $this->command->info('Klinika tapılmadı! Əvvəl ClinicSeeder işə salın.');
            return;
        }

        for ($i = 0; $i < $count; $i++) {
            $this->createSingleDoctor($categories, $services, $clinics, $i);
        }

        $this->command->info("$count həkim uğurla yaradıldı!");
    }

    private function createSingleDoctor($categories, $services, $clinics, $index): void
    {
        // Random həkim məlumatları
        $doctorInfo = collect($this->doctorNames)->random();
        $gender = fake()->randomElement([GenderEnum::Male, GenderEnum::Female]);

        // 1. User yaradırıq
        $user = User::create([
            'name' => $doctorInfo['name'],
            'surname' => $doctorInfo['surname'],
            'email' => 'doctor' . ($index + 1) . '@doctap.az',
            'password' => Hash::make('password123'),
            'username' => Str::slug($doctorInfo['name'] . '-' . $doctorInfo['surname']) . '-' . ($index + 1),
            'phone' => '+994' . fake('az_AZ')->randomNumber(9, true),
            'gender' => $gender,
            'birthdate' => fake()->dateTimeBetween('-65 years', '-25 years')->format('Y-m-d'),
            'user_type' => UserTypeEnum::Doctor,
            'status' => UserStatusEnum::Active,
            'email_verified_at' => now(),
        ]);

        // 2. User preferences yaradırıq
        UserPreference::create([
            'user_id' => $user->id,
            'language' => 'az',
            'notification_settings' => [
                'email_notifications' => true,
                'push_notifications' => true,
                'appointment_reminders' => true,
            ],
        ]);

        // 3. Doctor profili yaradırıq
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
            'is_verified' => fake()->boolean(80), // 80% verified
            'is_featured' => fake()->boolean(20), // 20% featured
            'years_of_experience' => fake()->numberBetween(2, 35),
            'practice_license_number' => 'LIC-' . fake()->unique()->numberBetween(100000, 999999),
            'workplace_name' => fake()->randomElement($this->workplaces),
            'workplace_address' => fake('az_AZ')->address,
            'workplace_phone' => '+994' . fake('az_AZ')->randomNumber(9, true),
            'available_for_home_visit' => fake()->boolean(30),
            'available_for_online_consultation' => fake()->boolean(70),
            'home_visit_fee' => fake()->boolean(30) ? fake()->randomFloat(2, 50, 300) : null,
            'online_consultation_fee' => fake()->boolean(70) ? fake()->randomFloat(2, 15, 100) : null,
            'social_media_links' => $this->generateSocialMediaLinks($user->username),
            'average_rating' => fake()->numberBetween(300, 500), // 3.0-5.0 arası
            'total_ratings' => fake()->numberBetween(5, 200),
            'total_patients' => fake()->numberBetween(50, 2000),
        ]);

        // 4. Təhsil məlumatları əlavə edirik
        $this->createEducation($doctor);

        // 5. İş təcrübəsi əlavə edirik
        $this->createExperience($doctor);

        // 6. Sertifikatlar əlavə edirik
        $this->createCertificates($doctor);

        // 7. Dil bilikləri əlavə edirik
        $this->createLanguages($doctor);

        // 8. Klinika əlaqələri yaradırıq
        $this->createClinicRelations($doctor, $clinics);

        // 9. Xidmətlər əlavə edirik
        $this->createDoctorServices($doctor, $services, $clinics);

        // 10. İş cədvəli yaradırıq
        $this->createSchedules($doctor, $clinics);

        // 11. Məşğulluq vaxtları əlavə edirik
        $this->createUnavailabilities($doctor);
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
        // 60% həkimlərin sosial media hesabları var
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
        $experienceCount = fake()->numberBetween(2, 5);

        for ($i = 0; $i < $experienceCount; $i++) {
            $isCurrent = $i === 0 && fake()->boolean(70); // İlk iş yeri hal-hazırki ola bilər

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
        $clinicCount = fake()->numberBetween(1, 3);
        $selectedClinics = $clinics->random($clinicCount);

        foreach ($selectedClinics as $index => $clinic) {
            // doctor_clinic pivot cədvəlinə direct məlumat əlavə edirik
            DB::table('doctor_clinic')->insert([
                'doctor_id' => $doctor->id,
                'clinic_id' => $clinic->id,
                'start_date' => fake()->dateTimeBetween('-5 years', '-1 year')->format('Y-m-d'),
                'end_date' => fake()->boolean(20) ? fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d') : null,
                'is_main_workplace' => $index === 0 ? 1 : 0, // İlk klinika əsas iş yeri
                'is_active' => fake()->boolean(90) ? 1 : 0,
                'note' => fake()->boolean(30) ? 'Həkim bu klinikada ' . fake()->numberBetween(1, 5) . ' il işləyib' : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function createDoctorServices(Doctor $doctor, $services, $clinics): void
    {
        // Həkimin kateqoriyasına uyğun xidmətləri seçirik
        $categoryServices = $services->where('category_id', $doctor->category);
        if ($categoryServices->isEmpty()) {
            // Əgər kateqoriyaya uyğun xidmət yoxdursa, random seçirik
            $categoryServices = $services->random(fake()->numberBetween(3, 8));
        } else {
            $categoryServices = $categoryServices->random(min($categoryServices->count(), fake()->numberBetween(3, 8)));
        }

        // Həkimin işlədiyi klinikları alırıq
        $doctorClinicIds = DB::table('doctor_clinic')
            ->where('doctor_id', $doctor->id)
            ->where('is_active', 1)
            ->pluck('clinic_id');

        // Əgər həkimin aktiv klinikası yoxdursa, skip edirik
        if ($doctorClinicIds->isEmpty()) {
            return;
        }

        foreach ($categoryServices as $service) {
            foreach ($doctorClinicIds as $clinicId) {
                DoctorClinicService::create([
                    'doctor_id' => $doctor->id,
                    'clinic_id' => $clinicId,
                    'service_id' => $service->id,
                    'price' => fake()->randomFloat(2, $service->price * 0.8, $service->price * 1.2),
                    'duration' => fake()->randomElement([30, 45, 60, 90]),
                    'description' => fake()->boolean(40) ? 'Həkimin bu xidmət üçün əlavə qeydi' : null,
                    'is_active' => fake()->boolean(95),
                ]);
            }
        }
    }

    private function createSchedules(Doctor $doctor, $clinics): void
    {
        // Həkimin işlədiyi klinikları alırıq
        $doctorClinicIds = DB::table('doctor_clinic')
            ->where('doctor_id', $doctor->id)
            ->where('is_active', 1)
            ->pluck('clinic_id');

        // Əgər həkimin aktiv klinikası yoxdursa, skip edirik
        if ($doctorClinicIds->isEmpty()) {
            return;
        }

        $workDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

        foreach ($doctorClinicIds as $clinicId) {
            foreach ($workDays as $day) {
                // Hər gün işləmir, bəzi günlər boş ola bilər
                if (fake()->boolean(85)) { // 85% ehtimalla işləyir
                    $startHour = fake()->numberBetween(8, 10);
                    $endHour = fake()->numberBetween(16, 19);

                    DoctorSchedule::create([
                        'doctor_id' => $doctor->id,
                        'clinic_id' => $clinicId,
                        'day_of_week' => $day,
                        'start_time' => sprintf('%02d:00:00', $startHour),
                        'end_time' => sprintf('%02d:00:00', $endHour),
                        'is_active' => true,
                        'max_appointments' => fake()->numberBetween(8, 20),
                        'appointment_duration' => fake()->randomElement([30, 45, 60]),
                        'note' => fake()->boolean(20) ? 'İş cədvəli qeydi' : null,
                    ]);
                }
            }
        }
    }

    private function createUnavailabilities(Doctor $doctor): void
    {
        // Həkimin işlədiyi klinikları alırıq
        $doctorClinicIds = DB::table('doctor_clinic')
            ->where('doctor_id', $doctor->id)
            ->where('is_active', 1)
            ->pluck('clinic_id');

        // Əgər həkimin aktiv klinikası yoxdursa, skip edirik
        if ($doctorClinicIds->isEmpty()) {
            return;
        }

        // Hər həkim üçün 2-6 məşğulluq yaradırıq
        $unavailabilityCount = fake()->numberBetween(2, 6);

        for ($i = 0; $i < $unavailabilityCount; $i++) {
            $clinicId = fake()->boolean(70) ? $doctorClinicIds->random() : null; // 70% ehtimalla konkret klinikaya aid

            // Məşğulluq növlərini təyin edirik
            $unavailabilityTypes = [
                ['reason' => 'Məzuniyyət', 'duration_days' => fake()->numberBetween(7, 21)],
                ['reason' => 'Xəstəlik məzuniyyəti', 'duration_days' => fake()->numberBetween(3, 10)],
                ['reason' => 'Konfrans/Seminar', 'duration_days' => fake()->numberBetween(1, 3)],
                ['reason' => 'Şəxsi məşğuliyyət', 'duration_days' => fake()->numberBetween(1, 2)],
                ['reason' => 'Tibbi müayinə', 'duration_days' => 1],
                ['reason' => 'Ailə mərasimi', 'duration_days' => fake()->numberBetween(1, 3)],
            ];

            $unavailabilityType = fake()->randomElement($unavailabilityTypes);

            // Keçmiş və gələcək tarixlər arasında seçim
            $isPast = fake()->boolean(60); // 60% keçmiş, 40% gələcək

            if ($isPast) {
                $startDate = fake()->dateTimeBetween('-6 months', '-1 week');
            } else {
                $startDate = fake()->dateTimeBetween('+1 week', '+3 months');
            }

            $endDate = (clone $startDate)->modify('+' . $unavailabilityType['duration_days'] . ' days');

            // Təkrarlanan məşğulluq (məs: hər həftə çərşənbə)
            $isRecurring = fake()->boolean(20); // 20% təkrarlanan
            $recurringPattern = null;

            if ($isRecurring) {
                $patterns = [
                    'weekly_wednesday' => 'Hər çərşənbə',
                    'monthly_first_friday' => 'Hər ayın ilk cüməsi',
                    'biweekly_monday' => 'İki həftədə bir bazar ertəsi',
                ];
                $recurringPattern = fake()->randomElement($patterns);
            }

            DoctorUnavailability::create([
                'doctor_id' => $doctor->id,
                'clinic_id' => $clinicId,
                'start_datetime' => $startDate->format('Y-m-d H:i:s'),
                'end_datetime' => $endDate->format('Y-m-d H:i:s'),
                'reason' => $unavailabilityType['reason'],
                'description' => $this->generateUnavailabilityDescription($unavailabilityType['reason']),
                'is_recurring' => $isRecurring,
                'recurring_pattern' => $recurringPattern,
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
            'Xəstəlik məzuniyyəti' => [
                'Sağlamlıq problemləri səbəbilə müvəqqəti əlçatmazlıq',
                'Tibbi müalicə prosesi',
                'Bərpa dövrü',
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
