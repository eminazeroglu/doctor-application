<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Review;
use App\Models\ReviewCriteria;
use App\Models\ReviewHelpful;
use App\Models\ReviewRating;
use App\Models\ReviewReport;
use App\Models\ReviewResponse;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Faker\Factory as Faker;

class ReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create('az_AZ');

        // Mövcud kriteriyaları əldə et, əgər yoxdursa yarat
        $doctorCriteria = $this->createCriteriaIfNotExists('doctor');
        $clinicCriteria = $this->createCriteriaIfNotExists('clinic');

        // Giriş məlumatları əldə et
        $patients = Patient::all();
        $doctors = Doctor::all();
        $clinics = Clinic::all();
        $appointments = Appointment::all();
        $adminUsers = User::where('user_type', 'admin')->get();

        // Əgər kifayət qədər məlumat yoxdursa, xəbərdarlıq et
        if ($patients->isEmpty()) {
            $this->command->error('Patients not found! Please seed patients first.');
            return;
        }

        if ($doctors->isEmpty()) {
            $this->command->error('Doctors not found! Please seed doctors first.');
            return;
        }

        if ($clinics->isEmpty()) {
            $this->command->error('Clinics not found! Please seed clinics first.');
            return;
        }

        $this->command->info('Creating 200 reviews...');

        $reviews = [];

        // 200 rəy yarat
        for ($i = 0; $i < 200; $i++) {
            // Təqribən yarısı həkimlər üçün, yarısı klinikalar üçün olsun
            $isForDoctor = $faker->boolean(60);

            $patient = $patients->random();
            $doctor = $isForDoctor ? $doctors->random() : null;
            $clinic = $isForDoctor ? null : $clinics->random();

            // Randevu əlaqəsi (bəzi rəylər üçün)
            $appointment = null;
            if ($faker->boolean(70)) {
                if ($isForDoctor && $appointments->isNotEmpty()) {
                    $doctorAppointments = $appointments->where('doctor_id', $doctor->id)
                        ->where('patient_id', $patient->id);
                    if ($doctorAppointments->isNotEmpty()) {
                        $appointment = $doctorAppointments->random();
                    }
                }
            }

            // Rəy mətni
            $rating = $faker->numberBetween(1, 5);
            $comment = $this->generateReviewText($faker, $rating, $isForDoctor);

            // Rəy statusları
            $isAnonymous = $faker->boolean(20); // 20% rəylər anonim
            $isVerified = $faker->boolean(90);  // 90% rəylər təsdiqlənmiş
            $isModerated = $faker->boolean(95); // 95% rəylər moderasiyadan keçmiş
            $isActive = $faker->boolean(98);    // 98% rəylər aktiv

            // Rəyi yarat
            $review = Review::create([
                'uuid' => Str::uuid(),
                'patient_id' => $patient->id,
                'doctor_id' => $doctor ? $doctor->id : null,
                'clinic_id' => $clinic ? $clinic->id : null,
                'appointment_id' => $appointment ? $appointment->id : null,
                'comment' => $comment,
                'rating' => $rating,
                'is_anonymous' => $isAnonymous,
                'is_verified' => $isVerified,
                'is_moderated' => $isModerated,
                'is_active' => $isActive,
                'created_at' => $faker->dateTimeBetween('-1 year', 'now'),
                'updated_at' => $faker->dateTimeBetween('-1 year', 'now'),
            ]);

            $reviews[] = $review;

            // Kriteriya qiymətləndirmələri
            $criteria = $isForDoctor ? $doctorCriteria : $clinicCriteria;
            foreach ($criteria as $criterion) {
                // Əsas qiymətləndirməyə yaxın bir qiymət ver, amma tam eyni olmasın
                $criteriaRating = max(1, min(5, $rating + $faker->randomElement([-1, 0, 0, 1])));

                ReviewRating::create([
                    'review_id' => $review->id,
                    'criteria_id' => $criterion->id,
                    'rating' => $criteriaRating,
                    'created_at' => $review->created_at,
                    'updated_at' => $review->created_at,
                ]);
            }

            // Bəzi rəylər üçün həkim və ya klinika cavabları
            if ($isVerified && $isModerated && $isActive && $faker->boolean(40)) {
                $this->createReviewResponse($faker, $review, $doctor, $clinic);
            }

            // Bəzi rəylər üçün faydalılıq qeydləri
            if ($isVerified && $isModerated && $isActive && $faker->boolean(60)) {
                $this->createHelpfulnessMarks($faker, $review, $patients);
            }
        }

        // Rəylər üçün şikayətlər yarat (təxminən 15% rəy üçün)
        $this->command->info('Creating review reports...');
        $this->createReviewReports($faker, $reviews, $patients, $adminUsers);

        $this->command->info('Reviews seeded successfully!');
    }

    /**
     * Rəy tipinə görə kriteriyaları yaradır
     *
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function createCriteriaIfNotExists($type)
    {
        $criteria = ReviewCriteria::where('type', $type)->get();

        if ($criteria->isEmpty()) {
            if ($type === 'doctor') {
                $criteriaData = [
                    ['name' => 'Peşəkarlıq', 'type' => 'doctor', 'weight' => 5, 'description' => 'Həkimin peşəkar biliklərinin səviyyəsi'],
                    ['name' => 'Münasibət', 'type' => 'doctor', 'weight' => 4, 'description' => 'Həkimin xəstəyə qarşı münasibəti'],
                    ['name' => 'İzahetmə bacarığı', 'type' => 'doctor', 'weight' => 3, 'description' => 'Həkimin tibbi məlumatları izah etmə bacarığı'],
                    ['name' => 'Dəqiqlik', 'type' => 'doctor', 'weight' => 3, 'description' => 'Həkimin vaxt qrafikinə riayət etməsi'],
                    ['name' => 'Qiymət-keyfiyyət nisbəti', 'type' => 'doctor', 'weight' => 2, 'description' => 'Xidmət qiymətinin keyfiyyətə nisbəti']
                ];
            } else {
                $criteriaData = [
                    ['name' => 'Təmizlik', 'type' => 'clinic', 'weight' => 5, 'description' => 'Klinikanın təmizlik səviyyəsi'],
                    ['name' => 'Rahatlıq', 'type' => 'clinic', 'weight' => 4, 'description' => 'Klinikanın rahatlığı və şəraiti'],
                    ['name' => 'Personal', 'type' => 'clinic', 'weight' => 4, 'description' => 'Klinika personalının münasibəti'],
                    ['name' => 'Əlçatanlıq', 'type' => 'clinic', 'weight' => 3, 'description' => 'Klinikanın yerləşməsi və əlçatanlığı'],
                    ['name' => 'Qiymət siyasəti', 'type' => 'clinic', 'weight' => 3, 'description' => 'Klinikanın qiymət siyasəti']
                ];
            }

            foreach ($criteriaData as $item) {
                ReviewCriteria::create([
                    'name' => $item['name'],
                    'type' => $item['type'],
                    'weight' => $item['weight'],
                    'description' => $item['description'],
                    'is_active' => true
                ]);
            }

            $criteria = ReviewCriteria::where('type', $type)->get();
        }

        return $criteria;
    }

    /**
     * Rating-ə görə uyğun rəy mətni yaradır
     *
     * @param \Faker\Generator $faker
     * @param int $rating
     * @param bool $isForDoctor
     * @return string
     */
    private function generateReviewText($faker, $rating, $isForDoctor)
    {
        $target = $isForDoctor ? 'həkim' : 'klinika';
        $highPraise = [
            "Əla $target! Hər şey çox peşəkar idi. Həqiqətən məmnun qaldım.",
            "Bu $target ilə təcrübəm mükəmməl oldu. Bütün gözləntilərimdən artıq idi.",
            "Uzun illər ərzində bir çox $target ilə işləmişəm, lakin bu, şübhəsiz ki, ən yaxşısıdır.",
            "Hamıya tövsiyə edirəm! $target çox diqqətli və qayğıkeşdir.",
            "Çox yüksək səviyyəli peşəkarlıq. Təşəkkür edirəm ki, belə $target var!"
        ];

        $goodPraise = [
            "Yaxşı $target. Qayğı və diqqətlə yanaşdı, məmnun qaldım.",
            "Ümumiyyətlə, yaxşı təcrübə idi. Müsbət təəssürat yaratdı.",
            "Mənə kömək etdi və professional yanaşdı. Tövsiyə edirəm.",
            "Gözlədiyimdən yaxşı çıxdı. Razı qaldım.",
            "Müalicəmdən razıyam. Yaxşı $target."
        ];

        $mediumPraise = [
            "Normal idi. Nə çox yaxşı, nə də pis.",
            "Orta səviyyədə $target. Bəzi şeylər yaxşı idi, bəziləri isə yox.",
            "Təkmilləşdirmək üçün bəzi sahələr var. Ümumiyyətlə, qənaətbəxşdir.",
            "$target yaxşı idi, amma gözləmə müddəti çox oldu.",
            "Xidmət normal idi, amma qiymətlər yüksəkdir."
        ];

        $lowPraise = [
            "Məyus oldum. Gözlədiyim səviyyədə deyildi.",
            "Çox vaxt itirildi və nəticə qənaətbəxş olmadı.",
            "Münasibət yaxşı deyildi. Təkmilləşmə lazımdır.",
            "Təəssüf ki, bu $target tövsiyə edə bilmərəm.",
            "Daha professional yanaşma gözləyirdim."
        ];

        $veryLowPraise = [
            "Tam olaraq mənfi təcrübə. Təəssüf edirəm ki, bu $target seçdim.",
            "Heç bir halda tövsiyə etmirəm. Çox pis təcrübə oldu.",
            "Zamanımı və pulumu boşa xərclədim. Qətiyyən məmnun deyiləm.",
            "Təəssüf ki, $target məni çox məyus etdi.",
            "Daha pis ola bilməzdi. Tamamilə peşman oldum."
        ];

        switch ($rating) {
            case 5:
                $comment = $faker->randomElement($highPraise);
                if ($faker->boolean(70)) {
                    $comment .= " " . $faker->randomElement([
                            "Bütün suallara ətraflı cavab verdi.",
                            "Çox diqqətlə qulaq asır və həqiqətən kömək etmək istəyir.",
                            "Yüksək səviyyəli texnologiyalar və avadanlıqlar var.",
                            "Növbəti dəfə də mütləq buraya müraciət edəcəyəm.",
                            "Ailə üzvlərimə də tövsiyə etmişəm."
                        ]);
                }
                break;

            case 4:
                $comment = $faker->randomElement($goodPraise);
                if ($faker->boolean(60)) {
                    $comment .= " " . $faker->randomElement([
                            "Sadəcə kiçik bir gözləmə problemi oldu.",
                            "Qiymətlər bir az yüksək olsa da, keyfiyyət yaxşıdır.",
                            "Bəzi kiçik çatışmazlıqlar var, amma ümumilikdə yaxşıdır.",
                            "Təkrar müraciət edərdim.",
                            "Tövsiyə edəcəyim bir $target."
                        ]);
                }
                break;

            case 3:
                $comment = $faker->randomElement($mediumPraise);
                if ($faker->boolean(50)) {
                    $comment .= " " . $faker->randomElement([
                            "Daha çox izahat verilə bilərdi.",
                            "Gözləmə vaxtı uzun idi.",
                            "Qiymətlər yüksək görünür.",
                            "Heç də pis deyil, amma mükəmməl də deyil.",
                            "Təkmilləşmə üçün imkanlar var."
                        ]);
                }
                break;

            case 2:
                $comment = $faker->randomElement($lowPraise);
                if ($faker->boolean(70)) {
                    $comment .= " " . $faker->randomElement([
                            "Çox gözləmək lazım gəldi və nəticə qənaətbəxş olmadı.",
                            "Suallarıma tam cavab ala bilmədim.",
                            "Texniki cəhətdən zəifdir.",
                            "Münasibət yaxşı deyildi.",
                            "Təəssüf ki, gözlədiyim kimi olmadı."
                        ]);
                }
                break;

            case 1:
                $comment = $faker->randomElement($veryLowPraise);
                if ($faker->boolean(80)) {
                    $comment .= " " . $faker->randomElement([
                            "Tamamilə peşəkar olmayan davranış.",
                            "Problemim həll olunmadı, əksinə daha da pisləşdi.",
                            "Vaxtımı və pulumu boşa xərclədim.",
                            "Qətiyyən tövsiyə etmirəm.",
                            "Bir daha bu $target müraciət etməyəcəyəm."
                        ]);
                }
                break;

            default:
                $comment = $faker->text(100);
        }

        // Bəzən uzun rəylər əlavə et
        if ($faker->boolean(30)) {
            $comment .= " " . $faker->paragraph(2);
        }

        return $comment;
    }

    /**
     * Rəy üçün cavab yaradır
     *
     * @param \Faker\Generator $faker
     * @param Review $review
     * @param Doctor|null $doctor
     * @param Clinic|null $clinic
     * @return void
     */
    private function createReviewResponse($faker, $review, $doctor, $clinic)
    {
        $isPositive = $review->rating >= 4;
        $isNegative = $review->rating <= 2;

        if ($doctor) {
            $user = User::find($doctor->user_id);
        } elseif ($clinic) {
            // Klinika adminini təmsil edəcək bir istifadəçi
            // (real sistemdə bu klinika ilə əlaqəli bir admin olacaq)
            $user = User::where('user_type', 'admin')->inRandomOrder()->first();
            if (!$user) {
                $user = User::inRandomOrder()->first(); // Hər hansı bir istifadəçi
            }
        } else {
            return;
        }

        if (!$user) return;

        $responseText = '';

        if ($isPositive) {
            $responseText = $faker->randomElement([
                "Müsbət rəyiniz üçün təşəkkür edirik! Sizi yenidən görməkdən məmnun olarıq.",
                "Rəyiniz üçün təşəkkür edirik. Xidmətlərimizdən məmnun qaldığınıza sevindik.",
                "Dəyərli vaxtınızı ayırıb rəy bildirdiyiniz üçün minnətdarıq. Sağlam günlər arzulayırıq!",
                "Müsbət sözləriniz üçün təşəkkür edirik. Həmişə xidmətinizdəyik!",
                "Rəyiniz bizim üçün çox dəyərlidir. Təşəkkür edirik və sağlam günlər arzulayırıq!"
            ]);
        } elseif ($isNegative) {
            $responseText = $faker->randomElement([
                "Rəyiniz üçün təşəkkür edirik. Təcrübənizin gözlədiyiniz kimi olmamasına təəssüflənirik. Xidmətimizi təkmilləşdirmək üçün əlaqə saxlayıb daha ətraflı məlumat verə bilərsiniz.",
                "Narazılığınızı bildirdiyiniz üçün təşəkkür edirik. Təcrübənizi araşdıracağıq və yaxşılaşdırmaq üçün tədbirlər görəcəyik.",
                "Rəyiniz üçün təşəkkür edirik. Sizin təcrübəniz bizim üçün önəmlidir və biz daim xidmətimizi təkmilləşdirməyə çalışırıq.",
                "Təəssüratınızı bizimlə bölüşdüyünüz üçün təşəkkür edirik. Bu cür rəylər bizə xidmətimizi təkmilləşdirməyə kömək edir.",
                "Narazılığınız üçün üzr istəyirik. Bizimlə əlaqə saxlayın ki, vəziyyəti daha yaxşı anlaya bilək və sizə kömək edə bilək."
            ]);
        } else {
            $responseText = $faker->randomElement([
                "Rəyiniz üçün təşəkkür edirik. Xidmətimizi daha da yaxşılaşdırmaq üçün əlimizdən gələni edəcəyik.",
                "Vaxt ayırıb rəy bildirdiyiniz üçün təşəkkür edirik. Təklif və iradlarınız bizim üçün çox dəyərlidir.",
                "Rəyinizi qiymətləndiririk. Hər zaman daha yaxşı xidmət göstərməyə çalışırıq.",
                "Sizin rəyiniz bizim üçün çox önəmlidir. Gələcəkdə xidmətimizi daha da təkmilləşdirmək üçün səy göstərəcəyik.",
                "Dəyərləndirməniz üçün təşəkkür edirik. Xidmətimizi təkmilləşdirmək üçün işləyirik."
            ]);
        }

        // Bəzən əlavə mətn əlavə et
        if ($faker->boolean(40)) {
            $responseText .= " " . $faker->randomElement([
                    "Sizə sağlam günlər arzulayırıq!",
                    "Hər hansı bir sualınız olarsa, bizimlə əlaqə saxlaya bilərsiniz.",
                    "Sizə xidmət etmək bizim üçün şərəfdir.",
                    "Sağlamlığınız bizim üçün önəmlidir.",
                    "Gələcəkdə də xidmətinizdə olmaqdan məmnunluq duyarıq."
                ]);
        }

        ReviewResponse::create([
            'review_id' => $review->id,
            'user_id' => $user->id,
            'response' => $responseText,
            'is_moderated' => $faker->boolean(95),
            'is_active' => $faker->boolean(98),
            'created_at' => $faker->dateTimeBetween($review->created_at, 'now'),
            'updated_at' => $faker->dateTimeBetween($review->created_at, 'now'),
        ]);
    }

    /**
     * Rəy üçün faydalılıq qeydləri yaradır
     *
     * @param \Faker\Generator $faker
     * @param Review $review
     * @param \Illuminate\Database\Eloquent\Collection $patients
     * @return void
     */
    private function createHelpfulnessMarks($faker, $review, $patients)
    {
        $helpfulCount = $faker->numberBetween(0, 10);

        for ($i = 0; $i < $helpfulCount; $i++) {
            $patient = $patients->random();

            // Eyni xəstə eyni rəyi bir dəfə qiymətləndirə bilər
            $exists = ReviewHelpful::where('review_id', $review->id)
                ->where('user_id', $patient->user_id)
                ->exists();

            if (!$exists) {
                // Yüksək qiymətli rəylər daha çox faydalı hesab edilsin
                $isHelpful = $review->rating >= 4 ? $faker->boolean(80) : $faker->boolean(40);

                ReviewHelpful::create([
                    'review_id' => $review->id,
                    'user_id' => $patient->user_id,
                    'is_helpful' => $isHelpful,
                    'created_at' => $faker->dateTimeBetween($review->created_at, 'now'),
                    'updated_at' => $faker->dateTimeBetween($review->created_at, 'now'),
                ]);
            }
        }
    }

    /**
     * Rəylər üçün şikayətlər yaradır
     *
     * @param \Faker\Generator $faker
     * @param array $reviews
     * @param \Illuminate\Database\Eloquent\Collection $patients
     * @param \Illuminate\Database\Eloquent\Collection $adminUsers
     * @return void
     */
    private function createReviewReports($faker, $reviews, $patients, $adminUsers)
    {
        // Rəylərin təxminən 15%-i üçün şikayət yaradır
        $reportCount = (int) ceil(count($reviews) * 0.15);
        $reportedReviews = $faker->randomElements($reviews, $reportCount);

        $reasons = [
            'spam' => 'Spam və ya reklam məzmunu',
            'offensive' => 'Təhqiredici məzmun',
            'inappropriate' => 'Uyğunsuz məzmun',
            'false_information' => 'Yanlış məlumat',
            'duplicate' => 'Təkrar rəy',
            'other' => 'Digər'
        ];

        $reasonNotes = [
            'spam' => [
                'Bu rəy reklam məqsədi daşıyır və əlaqəsizdir',
                'Spam məzmunu var, başqa klinika/həkimi reklam edir',
                'Anonim reklam və ya spam məzmunu',
                'Əlaqəsiz təbliğat məzmunu',
                'Təkrarlanan spam mesaj'
            ],
            'offensive' => [
                'Həkimə/klinikaya qarşı təhqiramiz ifadələr var',
                'Kobud və ədəbsiz ifadələr istifadə olunub',
                'Şəxsi həqarət və təhqir sözləri var',
                'Əxlaqsız və təhqiredici məzmun',
                'Ədəbsiz ifadələr istifadə olunub'
            ],
            'inappropriate' => [
                'Rəyin məzmunu tibbi kontekstdən kənardır',
                'Uyğunsuz və mənasız məzmun',
                'Tibbi xidmətlə əlaqəsi olmayan mövzular var',
                'Platforma qaydalarını pozur',
                'Kontekstdən kənar və uyğunsuz'
            ],
            'false_information' => [
                'Rəydə qeyd olunan faktlar həqiqətə uyğun deyil',
                'Yanlış tibbi məlumatlar var',
                'Həkim/klinika haqqında yalan məlumatlar',
                'Aldadıcı və təhrif edilmiş məlumatlar',
                'Müalicə haqqında yalan iddialar var'
            ],
            'duplicate' => [
                'Eyni istifadəçinin təkrar rəyidir',
                'Eyni məzmun başqa rəylərdə də yazılıb',
                'Kopiya edilmiş və təkrarlanan rəy',
                'Eyni şəxs tərəfindən dəfələrlə yazılıb',
                'Plagiat və təkrar edilən rəy'
            ],
            'other' => [
                'Rəydə başqa problemlər var',
                'Düzgün kateqoriyaya sığmayan şikayət',
                'Platformanın məqsədlərinə uyğun deyil',
                'Digər narahatlıq doğuran məsələlər',
                'Başqa qaydalar pozulub'
            ]
        ];

        foreach ($reportedReviews as $review) {
            // Rəyin əsasən aşağı qiymətli olanları şikayət edilsin
            if ($review->rating > 3 && !$faker->boolean(20)) {
                continue; // Yüksək qiymətli rəylərin əksəriyyəti şikayət edilmir
            }

            // Şikayətin təsadüfi təfərrüatları
            $reportReason = $faker->randomElement(array_keys($reasons));
            $isResolved = $faker->boolean(60); // 60% şikayətlər həll olunub

            // Şikayət edən istifadəçi (həkimlər də şikayət edə bilər)
            $reportingUser = null;
            if ($review->doctor_id && $faker->boolean(70)) {
                // Həkim haqqında mənfi rəy yazılıbsa, həkim özü şikayət edə bilər
                $reportingUser = User::find(Doctor::find($review->doctor_id)->user_id);
            } else {
                // Təsadüfi bir xəstə tərəfindən şikayət
                $reportingUser = $patients->random()->user;
            }

            if (!$reportingUser) {
                continue;
            }

            // Şikayəti yaratmaq üçün məlumatlar
            $reportData = [
                'review_id' => $review->id,
                'user_id' => $reportingUser->id,
                'reason' => $reportReason,
                'is_resolved' => $isResolved,
                'created_at' => $faker->dateTimeBetween($review->created_at, 'now'),
                'updated_at' => $faker->dateTimeBetween($review->created_at, 'now')
            ];

            // Əgər şikayət həll olunubsa, həll detallarını əlavə et
            if ($isResolved && $adminUsers->isNotEmpty()) {
                $admin = $adminUsers->random();
                $reportData['resolved_by'] = $admin->id;
                $reportData['resolved_at'] = $faker->dateTimeBetween($reportData['created_at'], 'now');

                // Həll qeydləri
                $resolutionTexts = [
                    'spam' => [
                        'Rəy spam olduğu üçün silindi',
                        'Rəy spam olduğu təsdiqləndi və deaktiv edildi',
                        'Spam məzmun olduğu üçün rəy silindi',
                        'Reklam məzmunlu rəy deaktiv edildi',
                        'Spam olduğu üçün deaktiv edildi'
                    ],
                    'offensive' => [
                        'Təhqiredici məzmun silinib, rəyin qalan hissəsi saxlanılıb',
                        'Təhqiredici ifadələr olduğu üçün deaktiv edildi',
                        'Təhqiredici məzmun təsdiqləndi və rəy silindi',
                        'Kobud ifadələr olduğu üçün deaktiv edildi',
                        'Təhqiramiz məzmun olduğu üçün silindi'
                    ],
                    'inappropriate' => [
                        'Uyğunsuz məzmun olduğu üçün deaktiv edildi',
                        'Rəydə qeyri-professional ifadələr var, deaktiv edildi',
                        'Platformaya uyğun olmayan məzmun olduğu üçün silindi',
                        'Uyğunsuz məzmun təsdiqləndi və rəy silindi',
                        'Rəy qaydaları pozduğu üçün deaktiv edildi'
                    ],
                    'false_information' => [
                        'Yanlış məlumatlar olduğu təsdiqləndi və düzəlişlər edildi',
                        'Həkim/klinika ilə danışıldı, rəydəki məlumatlar yanlışdır',
                        'Rəydəki faktlar yoxlanıldı və yanlış olduğu təsdiqləndi',
                        'Yanlış məlumatlar olduğu üçün rəy düzəldilib',
                        'Yoxlama nəticəsində rəydəki məlumatların düzgün olmadığı aşkarlandı'
                    ],
                    'duplicate' => [
                        'Təkrar rəy olduğu təsdiqləndi və silindi',
                        'Eyni istifadəçinin təkrar rəyi olduğu üçün silindi',
                        'Eyni məzmunla başqa rəylər var, bu rəy silindi',
                        'Təkrar olduğu üçün deaktiv edildi',
                        'Müxtəlif profillə eyni rəyi təkrar yazdığı üçün silindi'
                    ],
                    'other' => [
                        'Şikayət yoxlanıldı və həll edildi',
                        'Şikayət əsaslıdır, lazımi tədbirlər görüldü',
                        'Problem araşdırıldı və həll edildi',
                        'Şikayət təsdiqləndi və uyğun qərar verildi',
                        'Şikayət əsaslı hesab edildi və həll olundu'
                    ]
                ];

                $reportData['resolution_notes'] = $faker->randomElement($resolutionTexts[$reportReason]);

                // Əgər şikayət həll olunubsa, rəyi deaktiv et
                if ($faker->boolean(70)) {
                    $review->update(['is_active' => false]);
                }
            }

            ReviewReport::create($reportData);

            // Hər bir rəy üçün maksimum 2 şikayət yaradılsın
            if ($faker->boolean(30)) {
                // İkinci şikayəti yaratmaq üçün başqa bir istifadəçi seç
                $anotherUser = $patients->where('id', '!=', $reportingUser->id)->random()->user;

                if ($anotherUser) {
                    $anotherReason = $faker->randomElement(array_keys($reasons));

                    $anotherReportData = [
                        'review_id' => $review->id,
                        'user_id' => $anotherUser->id,
                        'reason' => $anotherReason,
                        'is_resolved' => $isResolved,
                        'created_at' => $faker->dateTimeBetween($review->created_at, 'now'),
                        'updated_at' => $faker->dateTimeBetween($review->created_at, 'now')
                    ];

                    if ($isResolved && $adminUsers->isNotEmpty()) {
                        $admin = $adminUsers->random();
                        $anotherReportData['resolved_by'] = $admin->id;
                        $anotherReportData['resolved_at'] = $faker->dateTimeBetween($anotherReportData['created_at'], 'now');
                        $anotherReportData['resolution_notes'] = $faker->randomElement($resolutionTexts[$anotherReason]);
                    }

                    ReviewReport::create($anotherReportData);
                }
            }
        }
    }
}
