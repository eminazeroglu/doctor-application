<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Service;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        Service::truncate();
        Schema::enableForeignKeyConstraints();

        // Terapevtik sahələr üçün xidmətlər
        $this->createTherapeuticServices();

        // Cərrahiyyə sahələri üçün xidmətlər
        $this->createSurgicalServices();

        // Diaqnostika üçün xidmətlər
        $this->createDiagnosticServices();

        // Pediatriya üçün xidmətlər
        $this->createPediatricServices();

        // Qadın sağlamlığı üçün xidmətlər
        $this->createWomensHealthServices();

        // Stomatoloji xidmətlər
        $this->createDentalServices();

        // Digər xidmətlər
        $this->createOtherServices();
    }

    /**
     * Terapevtik sahələr üçün xidmətlər
     */
    private function createTherapeuticServices(): void
    {
        // Terapevtik kateqoriyalar
        $category = Category::where('translates->az->name', 'Terapevtik sahələr')->first();

        if (!$category) {
            return;
        }

        // İlkin konsultasiya xidməti
        $this->createService([
            'category_id' => $category->id,
            'translates' => [
                'az' => [
                    'name' => 'İlkin konsultasiya',
                    'description' => 'Həkimin ilkin müayinəsi və məsləhəti. Şikayətlərin dinlənilməsi, anamnezin toplanması və ilkin diaqnozun qoyulması daxildir.',
                    'short_description' => 'İlkin müayinə və məsləhət',
                    'instructions' => 'Konsultasiya zamanı bütün mövcud şikayətlərinizi bildirin. Əvvəlki müalicələr və allergiyalar haqqında məlumat verin.',
                    'preparation' => 'Əvvəlki analizləri və müalicə sənədlərini gətirin. Qəbul saatından 10 dəqiqə əvvəl gəlmək tövsiyə olunur.'
                ],
                'en' => [
                    'name' => 'Initial consultation',
                    'description' => 'Initial examination and consultation with the doctor. Includes listening to complaints, collecting anamnesis and setting a preliminary diagnosis.',
                    'short_description' => 'Initial examination and consultation',
                    'instructions' => 'During the consultation, state all your existing complaints. Provide information about previous treatments and allergies.',
                    'preparation' => 'Bring previous analyzes and treatment documents. It is recommended to arrive 10 minutes before the appointment.'
                ]
            ],
            'price' => 50,
            'duration' => 30,
            'is_popular' => true,
            'order' => 1
        ]);

        // Təkrar konsultasiya xidməti
        $this->createService([
            'category_id' => $category->id,
            'translates' => [
                'az' => [
                    'name' => 'Təkrar konsultasiya',
                    'description' => 'Müalicə prosesinin monitorinqi və korreksiyası üçün təkrar qəbul. Analizlərin və müalicənin nəticələrinin qiymətləndirilməsi.',
                    'short_description' => 'Müalicənin gedişinin qiymətləndirilməsi',
                    'instructions' => 'Təkrar konsultasiya zamanı müalicə prosesindəki dəyişiklikləri bildirin.',
                    'preparation' => 'Təyin olunmuş analizlərin nəticələrini gətirin.'
                ],
                'en' => [
                    'name' => 'Follow-up consultation',
                    'description' => 'Re-consultation for monitoring and correction of the treatment process. Evaluation of test results and treatment outcomes.',
                    'short_description' => 'Evaluation of treatment progress',
                    'instructions' => 'Report changes in the treatment process during the re-consultation.',
                    'preparation' => 'Bring the results of the prescribed tests.'
                ]
            ],
            'price' => 40,
            'duration' => 20,
            'is_popular' => false,
            'order' => 2
        ]);

        // Müalicə planı
        $this->createService([
            'category_id' => $category->id,
            'translates' => [
                'az' => [
                    'name' => 'Fərdi müalicə planı',
                    'description' => 'Xəstənin vəziyyətinə uyğun detallı müalicə planının hazırlanması. Dərman preparatları, prosedurlar, pəhriz və həyat tərzi dəyişiklikləri daxildir.',
                    'short_description' => 'Detallı müalicə planı',
                    'instructions' => 'Müalicə planı üçün ətraflı məsləhət veriləcək.',
                    'preparation' => 'Bütün əvvəlki müalicə və analizlərin nəticələrini gətirin.'
                ],
                'en' => [
                    'name' => 'Individual treatment plan',
                    'description' => 'Development of a detailed treatment plan according to the patient\'s condition. Includes medications, procedures, diet and lifestyle changes.',
                    'short_description' => 'Detailed treatment plan',
                    'instructions' => 'Detailed advice will be given for the treatment plan.',
                    'preparation' => 'Bring all previous treatment and analysis results.'
                ]
            ],
            'price' => 70,
            'duration' => 45,
            'is_popular' => true,
            'order' => 3
        ]);
    }

    /**
     * Cərrahiyyə sahələri üçün xidmətlər
     */
    private function createSurgicalServices(): void
    {
        // Cərrahiyyə kateqoriyaları
        $category = Category::where('translates->az->name', 'Cərrahiyyə sahələri')->first();

        if (!$category) {
            return;
        }

        // Cərrahi konsultasiya
        $this->createService([
            'category_id' => $category->id,
            'translates' => [
                'az' => [
                    'name' => 'Cərrahi konsultasiya',
                    'description' => 'Cərrah tərəfindən müayinə və cərrahi müdaxilə ehtiyacının qiymətləndirilməsi.',
                    'short_description' => 'Cərrahi müayinə və qiymətləndirmə',
                    'instructions' => 'Cərrah bütün mövcud şikayətləri və simptomları qiymətləndirəcək.',
                    'preparation' => 'Əvvəlki müayinə və əməliyyat sənədlərini gətirin.'
                ],
                'en' => [
                    'name' => 'Surgical consultation',
                    'description' => 'Examination by a surgeon and assessment of the need for surgical intervention.',
                    'short_description' => 'Surgical examination and assessment',
                    'instructions' => 'The surgeon will evaluate all existing complaints and symptoms.',
                    'preparation' => 'Bring previous examination and operation documents.'
                ]
            ],
            'price' => 80,
            'duration' => 40,
            'is_popular' => true,
            'order' => 1
        ]);

        // Əməliyyat planlaması
        $this->createService([
            'category_id' => $category->id,
            'translates' => [
                'az' => [
                    'name' => 'Əməliyyat planlaması',
                    'description' => 'Cərrahi əməliyyatın planlaşdırılması, risklər və əməliyyatdan sonrakı proseslər haqqında məlumatlandırma.',
                    'short_description' => 'Cərrahi əməliyyatın detallı planlaşdırılması',
                    'instructions' => 'Əməliyyat planlaması zamanı bütün suallarınızı verin.',
                    'preparation' => 'Bütün tibbi sənədləri və analizləri gətirin.'
                ],
                'en' => [
                    'name' => 'Surgery planning',
                    'description' => 'Planning of surgical operation, informing about risks and post-operative processes.',
                    'short_description' => 'Detailed planning of surgical operation',
                    'instructions' => 'Ask all your questions during the operation planning.',
                    'preparation' => 'Bring all medical documents and tests.'
                ]
            ],
            'price' => 100,
            'duration' => 60,
            'is_popular' => false,
            'order' => 2
        ]);

        // Əməliyyatdan sonrakı müşahidə
        $this->createService([
            'category_id' => $category->id,
            'translates' => [
                'az' => [
                    'name' => 'Əməliyyatdan sonrakı müşahidə',
                    'description' => 'Cərrahi əməliyyatdan sonra müşahidə və reabilitasiya prosesinin idarə edilməsi.',
                    'short_description' => 'Əməliyyatdan sonra müşahidə və qayğı',
                    'instructions' => 'Əməliyyatdan sonrakı bütün simptomları və narahatlıqları bildirin.',
                    'preparation' => 'Əməliyyat sənədlərini və göstərişləri gətirin.'
                ],
                'en' => [
                    'name' => 'Post-operative monitoring',
                    'description' => 'Post-surgical monitoring and management of the rehabilitation process.',
                    'short_description' => 'Post-operative monitoring and care',
                    'instructions' => 'Report all symptoms and concerns after surgery.',
                    'preparation' => 'Bring operation documents and instructions.'
                ]
            ],
            'price' => 60,
            'duration' => 30,
            'is_popular' => true,
            'order' => 3
        ]);
    }

    /**
     * Diaqnostika üçün xidmətlər
     */
    private function createDiagnosticServices(): void
    {
        // Diaqnostika kateqoriyaları
        $category = Category::where('translates->az->name', 'Diaqnostika və laboratoriya')->first();

        if (!$category) {
            return;
        }

        // Tam qan analizi
        $this->createService([
            'category_id' => $category->id,
            'translates' => [
                'az' => [
                    'name' => 'Tam qan analizi',
                    'description' => 'Qanın ümumi analizi (hemoglobin, leykositlər, eritrositlər, trombositlər, EÇS və s.).',
                    'short_description' => 'Qanın ümumi analizi',
                    'instructions' => 'Analiz nəticələri 24 saat ərzində hazır olacaq.',
                    'preparation' => 'Analizdən 8-10 saat əvvəl qida qəbul etməyin. Səhər yeməyindən öncə analizi vermək tövsiyə olunur.'
                ],
                'en' => [
                    'name' => 'Complete blood count',
                    'description' => 'General blood analysis (hemoglobin, leukocytes, erythrocytes, platelets, ESR, etc.).',
                    'short_description' => 'General blood analysis',
                    'instructions' => 'Analysis results will be ready within 24 hours.',
                    'preparation' => 'Do not eat 8-10 hours before the analysis. It is recommended to give the analysis before breakfast.'
                ]
            ],
            'price' => 30,
            'duration' => 15,
            'is_popular' => true,
            'order' => 1
        ]);

        // Ultrasəs müayinəsi
        $this->createService([
            'category_id' => $category->id,
            'translates' => [
                'az' => [
                    'name' => 'Ultrasəs müayinəsi (USM)',
                    'description' => 'Daxili orqanların ultrasəs vasitəsilə müayinəsi və qiymətləndirilməsi.',
                    'short_description' => 'Daxili orqanların USM müayinəsi',
                    'instructions' => 'Müayinə ağrısız və təhlükəsizdir.',
                    'preparation' => 'Qarın boşluğu USM üçün 6-8 saat ac qalın və müayinədən əvvəl 1 litr su için.'
                ],
                'en' => [
                    'name' => 'Ultrasound examination (USG)',
                    'description' => 'Examination and evaluation of internal organs by ultrasound.',
                    'short_description' => 'USG examination of internal organs',
                    'instructions' => 'The examination is painless and safe.',
                    'preparation' => 'For abdominal USG, stay hungry for 6-8 hours and drink 1 liter of water before the examination.'
                ]
            ],
            'price' => 70,
            'duration' => 30,
            'is_popular' => true,
            'order' => 2
        ]);

        // Kompüter tomoqrafiyası
        $this->createService([
            'category_id' => $category->id,
            'translates' => [
                'az' => [
                    'name' => 'Kompüter tomoqrafiyası (KT)',
                    'description' => 'Orqanizmin daxili strukturlarının yüksək dəqiqliklə üçölçülü təsvirlərinin alınması.',
                    'short_description' => 'Üçölçülü diaqnostik müayinə',
                    'instructions' => 'Müayinə zamanı sakit uzanmaq lazımdır. Nəticələr 24 saat ərzində hazır olacaq.',
                    'preparation' => 'Metal əşyaları çıxarın. Kontrastla müayinə üçün 4-6 saat ac qalın.'
                ],
                'en' => [
                    'name' => 'Computed tomography (CT)',
                    'description' => 'Obtaining high-precision three-dimensional images of internal structures of the body.',
                    'short_description' => 'Three-dimensional diagnostic examination',
                    'instructions' => 'It is necessary to lie still during the examination. Results will be ready within 24 hours.',
                    'preparation' => 'Remove metal objects. Stay hungry for 4-6 hours for examination with contrast.'
                ]
            ],
            'price' => 200,
            'duration' => 45,
            'is_popular' => false,
            'order' => 3
        ]);
    }

    /**
     * Pediatriya üçün xidmətlər
     */
    private function createPediatricServices(): void
    {
        // Pediatriya kateqoriyaları
        $category = Category::where('translates->az->name', 'Pediatriya')->first();

        if (!$category) {
            return;
        }

        // Pediatr konsultasiyası
        $this->createService([
            'category_id' => $category->id,
            'translates' => [
                'az' => [
                    'name' => 'Pediatr konsultasiyası',
                    'description' => 'Uşaq həkimi tərəfindən müayinə və məsləhət. Uşağın inkişafının və sağlamlıq vəziyyətinin qiymətləndirilməsi.',
                    'short_description' => 'Uşaq həkimi konsultasiyası',
                    'instructions' => 'Uşağın bütün narahatlıqları və simptomları haqqında məlumat verin.',
                    'preparation' => 'Uşağın tibb kartını və əvvəlki müayinə sənədlərini gətirin.'
                ],
                'en' => [
                    'name' => 'Pediatric consultation',
                    'description' => 'Examination and consultation by a pediatrician. Assessment of the child\'s development and health status.',
                    'short_description' => 'Pediatrician consultation',
                    'instructions' => 'Provide information about all concerns and symptoms of the child.',
                    'preparation' => 'Bring the child\'s medical card and previous examination documents.'
                ]
            ],
            'price' => 60,
            'duration' => 30,
            'is_popular' => true,
            'order' => 1
        ]);

        // Peyvənd
        $this->createService([
            'category_id' => $category->id,
            'translates' => [
                'az' => [
                    'name' => 'Peyvənd xidməti',
                    'description' => 'Peyvənd təqviminə uyğun olaraq və ya əlavə peyvəndlərin vurulması.',
                    'short_description' => 'Peyvəndlərin vurulması',
                    'instructions' => 'Peyvənddən sonra yarım saat klinikada qalmaq tövsiyə olunur.',
                    'preparation' => 'Uşağın peyvənd kartını gətirin və əvvəlki reaksiyalar haqqında məlumat verin.'
                ],
                'en' => [
                    'name' => 'Vaccination service',
                    'description' => 'Administration of vaccines according to the vaccination calendar or additional vaccines.',
                    'short_description' => 'Administration of vaccines',
                    'instructions' => 'It is recommended to stay in the clinic for half an hour after vaccination.',
                    'preparation' => 'Bring the child\'s vaccination card and provide information about previous reactions.'
                ]
            ],
            'price' => 40,
            'duration' => 20,
            'is_popular' => true,
            'order' => 2
        ]);

        // Uşaq inkişaf monitorinqi
        $this->createService([
            'category_id' => $category->id,
            'translates' => [
                'az' => [
                    'name' => 'Uşaq inkişaf monitorinqi',
                    'description' => 'Uşağın fiziki və psixomotor inkişafının dövri olaraq izlənməsi və qiymətləndirilməsi.',
                    'short_description' => 'Uşaq inkişafının qiymətləndirilməsi',
                    'instructions' => 'Monitorinq hər 3-6 ayda bir aparılır.',
                    'preparation' => 'Uşağın inkişaf gündəliyini və əvvəlki müayinə sənədlərini gətirin.'
                ],
                'en' => [
                    'name' => 'Child development monitoring',
                    'description' => 'Periodic monitoring and evaluation of the child\'s physical and psychomotor development.',
                    'short_description' => 'Assessment of child development',
                    'instructions' => 'Monitoring is performed every 3-6 months.',
                    'preparation' => 'Bring the child\'s development diary and previous examination documents.'
                ]
            ],
            'price' => 80,
            'duration' => 45,
            'is_popular' => false,
            'order' => 3
        ]);
    }

    /**
     * Qadın sağlamlığı üçün xidmətlər
     */
    private function createWomensHealthServices(): void
    {
        // Qadın sağlamlığı kateqoriyası
        $category = Category::where('translates->az->name', 'Qadın sağlamlığı')->first();

        if (!$category) {
            return;
        }

        // Ginekoloji müayinə
        $this->createService([
            'category_id' => $category->id,
            'translates' => [
                'az' => [
                    'name' => 'Ginekoloji müayinə',
                    'description' => 'Ginekoloq tərəfindən tam müayinə və konsultasiya. PAP test, USM və digər müayinələr daxil ola bilər.',
                    'short_description' => 'Qadın xəstəliklərinin müayinəsi',
                    'instructions' => 'Müayinə zamanı bütün narahatlıqlarınızı bildirin.',
                    'preparation' => 'Menstruasiya dövründə olmayın. Son menstruasiya tarixi haqqında məlumat verin.'
                ],
                'en' => [
                    'name' => 'Gynecological examination',
                    'description' => 'Complete examination and consultation by a gynecologist. May include PAP test, USG and other examinations.',
                    'short_description' => 'Examination of female diseases',
                    'instructions' => 'Report all your concerns during the examination.',
                    'preparation' => 'Do not be in the menstruation period. Provide information about the date of last menstruation.'
                ]
            ],
            'price' => 70,
            'duration' => 40,
            'is_popular' => true,
            'order' => 1
        ]);

        // Hamiləlik müşahidəsi
        $this->createService([
            'category_id' => $category->id,
            'translates' => [
                'az' => [
                    'name' => 'Hamiləlik müşahidəsi',
                    'description' => 'Hamiləlik dövründə ana və döl sağlamlığının müntəzəm monitorinqi.',
                    'short_description' => 'Hamiləliyin monitorinqi',
                    'instructions' => 'Hər müayinədə çəki, təzyiq, döl ürək tonları yoxlanılır.',
                    'preparation' => 'Əvvəlki müayinə sənədlərini və analizləri gətirin.'
                ],
                'en' => [
                    'name' => 'Pregnancy monitoring',
                    'description' => 'Regular monitoring of maternal and fetal health during pregnancy.',
                    'short_description' => 'Pregnancy monitoring',
                    'instructions' => 'Weight, pressure, fetal heart tones are checked at each examination.',
                    'preparation' => 'Bring previous examination documents and analyzes.'
                ]
            ],
            'price' => 90,
            'duration' => 30,
            'is_popular' => true,
            'order' => 2
        ]);

        // Kolposkopiya
        $this->createService([
            'category_id' => $category->id,
            'translates' => [
                'az' => [
                    'name' => 'Kolposkopiya',
                    'description' => 'Uşaqlıq boynunun xüsusi optik cihazla (kolposkop) ətraflı müayinəsi.',
                    'short_description' => 'Uşaqlıq boynunun diaqnostikası',
                    'instructions' => 'Prosedur ağrısızdır və 15-20 dəqiqə çəkir.',
                    'preparation' => 'Prosedurdan 48 saat əvvəl cinsi əlaqədən çəkinin. Menstruasiya dövründə olmayın.'
                ],
                'en' => [
                    'name' => 'Colposcopy',
                    'description' => 'Detailed examination of the cervix with a special optical device (colposcope).',
                    'short_description' => 'Diagnosis of the cervix',
                    'instructions' => 'The procedure is painless and takes 15-20 minutes.',
                    'preparation' => 'Avoid sexual intercourse 48 hours before the procedure. Do not be in the menstruation period.'
                ]
            ],
            'price' => 120,
            'duration' => 30,
            'is_popular' => false,
            'order' => 3
        ]);
    }

    /**
     * Stomatoloji xidmətlər
     */
    private function createDentalServices(): void
    {
        // Stomatoloji kateqoriya
        $category = Category::where('translates->az->name', 'Stomatoloji xidmətlər')->first();

        if (!$category) {
            return;
        }

        // Diş müayinəsi
        $this->createService([
            'category_id' => $category->id,
            'translates' => [
                'az' => [
                    'name' => 'Diş müayinəsi',
                    'description' => 'Ağız boşluğunun və dişlərin tam müayinəsi. Müalicə planının hazırlanması.',
                    'short_description' => 'Dişlərin müayinəsi',
                    'instructions' => 'Müayinə ağrısız və 20-30 dəqiqə çəkir.',
                    'preparation' => 'Müayinədən əvvəl dişlərinizi fırçalayın.'
                ],
                'en' => [
                    'name' => 'Dental examination',
                    'description' => 'Complete examination of the oral cavity and teeth. Preparation of treatment plan.',
                    'short_description' => 'Examination of teeth',
                    'instructions' => 'The examination is painless and takes 20-30 minutes.',
                    'preparation' => 'Brush your teeth before the examination.'
                ]
            ],
            'price' => 50,
            'duration' => 30,
            'is_popular' => true,
            'order' => 1
        ]);

        // Diş plomblanması
        $this->createService([
            'category_id' => $category->id,
            'translates' => [
                'az' => [
                    'name' => 'Diş plomblanması',
                    'description' => 'Diş karieslərinin təmizlənməsi və plomblanması. Müasir və keyfiyyətli plomb materiallarının istifadəsi.',
                    'short_description' => 'Diş karieslərinin müalicəsi',
                    'instructions' => 'Prosedur ağrısız keçir. Anesteziya tətbiq olunur.',
                    'preparation' => 'Əvvəlcədən hazırlıq tələb olunmur.'
                ],
                'en' => [
                    'name' => 'Dental filling',
                    'description' => 'Cleaning and filling of dental caries. Use of modern and high-quality filling materials.',
                    'short_description' => 'Treatment of dental caries',
                    'instructions' => 'The procedure is painless. Anesthesia is applied.',
                    'preparation' => 'No preparation is required in advance.'
                ]
            ],
            'price' => 100,
            'duration' => 60,
            'is_popular' => true,
            'order' => 2
        ]);

        // Diş implantasiyası
        $this->createService([
            'category_id' => $category->id,
            'translates' => [
                'az' => [
                    'name' => 'Diş implantasiyası',
                    'description' => 'İtmiş dişlərin əvəz edilməsi üçün müasir implantasiya texnologiyaları. Keyfiyyətli implantatların istifadəsi.',
                    'short_description' => 'İtmiş dişlərin əvəz edilməsi',
                    'instructions' => 'Prosedur mərhələli şəkildə aparılır və bir neçə vizit tələb edir.',
                    'preparation' => 'Əməliyyatdan əvvəl analizlər tələb olunur. Konsultasiya zamanı ətraflı məlumat veriləcək.'
                ],
                'en' => [
                    'name' => 'Dental implantation',
                    'description' => 'Modern implantation technologies for replacement of lost teeth. Use of high-quality implants.',
                    'short_description' => 'Replacement of lost teeth',
                    'instructions' => 'The procedure is carried out in stages and requires several visits.',
                    'preparation' => 'Tests are required before surgery. Detailed information will be provided during the consultation.'
                ]
            ],
            'price' => 800,
            'duration' => 120,
            'is_popular' => false,
            'order' => 3
        ]);
    }

    /**
     * Digər xidmətlər
     */
    private function createOtherServices(): void
    {
        // Digər kateqoriyalar üçün xidmətlər yarada bilərsiniz
        // Bura əlavə xidmətlər üçün kod əlavə edilə bilər
    }

    /**
     * Xidmət yaradır
     */
    private function createService(array $data): void
    {
        Service::create(array_merge([
            'slug' => $data['slug'] ?? Str::slug($data['translates']['az']['name']),
            'is_active' => true,
        ], $data));
    }
}
