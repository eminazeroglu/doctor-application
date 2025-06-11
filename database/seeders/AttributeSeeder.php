<?php

namespace Database\Seeders;

use App\Enums\AttributePositionEnum;
use App\Enums\AttributeTypeEnum;
use App\Models\Attribute;
use App\Models\AttributeOption;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class AttributeSeeder extends Seeder
{
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        Attribute::truncate();
        AttributeOption::truncate();

        // Həkimlərin əsas atributları
        $this->createDoctorBasicAttributes();

        // Həkimlərin təhsil və təcrübəsi ilə bağlı atributlar
        $this->createDoctorEducationAttributes();

        // Həkimlərin iş rejimi və qəbul ilə bağlı atributlar
        $this->createDoctorScheduleAttributes();

        // Həkimlərin xidmətləri ilə bağlı atributlar
        $this->createDoctorServicesAttributes();

        // Həkimlərin lisenziya və sertifikatları ilə bağlı atributlar
        $this->createDoctorCertificationsAttributes();

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Həkimlərin əsas atributları
     */
    private function createDoctorBasicAttributes(): void
    {
        // Cinsiyyət
        $gender = $this->createAttribute([
            'translates' => [
                'az' => ['name' => 'Cinsiyyət', 'description' => 'Həkimin cinsiyyəti'],
                'en' => ['name' => 'Gender', 'description' => 'Doctor\'s gender']
            ],
            'type' => AttributeTypeEnum::Select,
            'order' => 1
        ]);

        // Cinsiyyət seçimləri
        $this->createAttributeOptions($gender->id, [
            ['translates' => ['az' => ['name' => 'Kişi'], 'en' => ['name' => 'Male']]],
            ['translates' => ['az' => ['name' => 'Qadın'], 'en' => ['name' => 'Female']]]
        ]);

        // İxtisas dərəcəsi
        $qualification = $this->createAttribute([
            'translates' => [
                'az' => ['name' => 'İxtisas dərəcəsi', 'description' => 'Həkimin ixtisas dərəcəsi'],
                'en' => ['name' => 'Qualification', 'description' => 'Doctor\'s qualification level']
            ],
            'type' => AttributeTypeEnum::Select,
            'order' => 2
        ]);

        // İxtisas dərəcəsi seçimləri
        $this->createAttributeOptions($qualification->id, [
            ['translates' => ['az' => ['name' => 'İkinci dərəcəli həkim'], 'en' => ['name' => 'Second category doctor']]],
            ['translates' => ['az' => ['name' => 'Birinci dərəcəli həkim'], 'en' => ['name' => 'First category doctor']]],
            ['translates' => ['az' => ['name' => 'Ali dərəcəli həkim'], 'en' => ['name' => 'Highest category doctor']]],
            ['translates' => ['az' => ['name' => 'Tibb elmləri namizədi'], 'en' => ['name' => 'Candidate of Medical Sciences']]],
            ['translates' => ['az' => ['name' => 'Tibb elmləri doktoru'], 'en' => ['name' => 'Doctor of Medical Sciences']]],
            ['translates' => ['az' => ['name' => 'Professor'], 'en' => ['name' => 'Professor']]],
            ['translates' => ['az' => ['name' => 'Akademik'], 'en' => ['name' => 'Academician']]]
        ]);

        // Qəbul qiyməti
        $price = $this->createAttribute([
            'translates' => [
                'az' => ['name' => 'Qəbul qiyməti', 'description' => 'Həkimin qəbul qiyməti'],
                'en' => ['name' => 'Consultation fee', 'description' => 'Doctor\'s consultation fee']
            ],
            'type' => AttributeTypeEnum::Price,
            'order' => 3,
            'custom_fields' => [
                'min' => 0,
                'max' => 1000,
                'step' => 5,
                'suffix' => 'AZN'
            ]
        ]);

        // Qiymət aralığı
        $priceRange = $this->createAttribute([
            'translates' => [
                'az' => ['name' => 'Qiymət aralığı', 'description' => 'Qəbul qiymətinin aralığı'],
                'en' => ['name' => 'Price range', 'description' => 'Consultation fee range']
            ],
            'type' => AttributeTypeEnum::Select,
            'order' => 4
        ]);

        // Qiymət aralığı seçimləri
        $this->createAttributeOptions($priceRange->id, [
            ['translates' => ['az' => ['name' => '0-50 AZN'], 'en' => ['name' => '0-50 AZN']]],
            ['translates' => ['az' => ['name' => '50-100 AZN'], 'en' => ['name' => '50-100 AZN']]],
            ['translates' => ['az' => ['name' => '100-150 AZN'], 'en' => ['name' => '100-150 AZN']]],
            ['translates' => ['az' => ['name' => '150-200 AZN'], 'en' => ['name' => '150-200 AZN']]],
            ['translates' => ['az' => ['name' => '200+ AZN'], 'en' => ['name' => '200+ AZN']]]
        ]);

        // Danışdığı dillər
        $languages = $this->createAttribute([
            'translates' => [
                'az' => ['name' => 'Danışdığı dillər', 'description' => 'Həkimin danışdığı dillər'],
                'en' => ['name' => 'Languages spoken', 'description' => 'Languages spoken by the doctor']
            ],
            'type' => AttributeTypeEnum::MultiSelect,
            'order' => 5
        ]);

        // Dil seçimləri
        $this->createAttributeOptions($languages->id, [
            ['translates' => ['az' => ['name' => 'Azərbaycan dili'], 'en' => ['name' => 'Azerbaijani']]],
            ['translates' => ['az' => ['name' => 'Rus dili'], 'en' => ['name' => 'Russian']]],
            ['translates' => ['az' => ['name' => 'İngilis dili'], 'en' => ['name' => 'English']]],
            ['translates' => ['az' => ['name' => 'Türk dili'], 'en' => ['name' => 'Turkish']]],
            ['translates' => ['az' => ['name' => 'Ərəb dili'], 'en' => ['name' => 'Arabic']]],
            ['translates' => ['az' => ['name' => 'Fars dili'], 'en' => ['name' => 'Persian']]],
            ['translates' => ['az' => ['name' => 'Alman dili'], 'en' => ['name' => 'German']]],
            ['translates' => ['az' => ['name' => 'Fransız dili'], 'en' => ['name' => 'French']]]
        ]);

        // Yaş həddi
        $ageGroup = $this->createAttribute([
            'translates' => [
                'az' => ['name' => 'Yaş həddi', 'description' => 'Həkimin xidmət göstərdiyi yaş qrupları'],
                'en' => ['name' => 'Age group', 'description' => 'Age groups served by the doctor']
            ],
            'type' => AttributeTypeEnum::MultiSelect,
            'order' => 6
        ]);

        // Yaş qrupu seçimləri
        $this->createAttributeOptions($ageGroup->id, [
            ['translates' => ['az' => ['name' => 'Körpələr (0-1 yaş)'], 'en' => ['name' => 'Infants (0-1 years)']]],
            ['translates' => ['az' => ['name' => 'Uşaqlar (1-12 yaş)'], 'en' => ['name' => 'Children (1-12 years)']]],
            ['translates' => ['az' => ['name' => 'Yeniyetmələr (12-18 yaş)'], 'en' => ['name' => 'Teenagers (12-18 years)']]],
            ['translates' => ['az' => ['name' => 'Böyüklər (18-65 yaş)'], 'en' => ['name' => 'Adults (18-65 years)']]],
            ['translates' => ['az' => ['name' => 'Yaşlılar (65+ yaş)'], 'en' => ['name' => 'Elderly (65+ years)']]]
        ]);

        // Reytinq
        $rating = $this->createAttribute([
            'translates' => [
                'az' => ['name' => 'Reytinq', 'description' => 'Həkimin reytinqi'],
                'en' => ['name' => 'Rating', 'description' => 'Doctor\'s rating']
            ],
            'type' => AttributeTypeEnum::Select,
            'order' => 7
        ]);

        // Reytinq seçimləri
        $this->createAttributeOptions($rating->id, [
            ['translates' => ['az' => ['name' => '5 ulduz'], 'en' => ['name' => '5 stars']]],
            ['translates' => ['az' => ['name' => '4+ ulduz'], 'en' => ['name' => '4+ stars']]],
            ['translates' => ['az' => ['name' => '3+ ulduz'], 'en' => ['name' => '3+ stars']]],
            ['translates' => ['az' => ['name' => '2+ ulduz'], 'en' => ['name' => '2+ stars']]],
            ['translates' => ['az' => ['name' => '1+ ulduz'], 'en' => ['name' => '1+ stars']]]
        ]);
    }

    /**
     * Həkimlərin təhsil və təcrübəsi ilə bağlı atributlar
     */
    private function createDoctorEducationAttributes(): void
    {
        // Təhsil müəssisəsi
        $education = $this->createAttribute([
            'translates' => [
                'az' => ['name' => 'Təhsil müəssisəsi', 'description' => 'Həkimin təhsil aldığı müəssisə'],
                'en' => ['name' => 'Education institution', 'description' => 'Doctor\'s education institution']
            ],
            'type' => AttributeTypeEnum::Select,
            'order' => 8
        ]);

        // Təhsil müəssisəsi seçimləri
        $this->createAttributeOptions($education->id, [
            ['translates' => ['az' => ['name' => 'Azərbaycan Tibb Universiteti'], 'en' => ['name' => 'Azerbaijan Medical University']]],
            ['translates' => ['az' => ['name' => 'Bakı Dövlət Universiteti'], 'en' => ['name' => 'Baku State University']]],
            ['translates' => ['az' => ['name' => 'I.M. Seçenov adına Birinci Moskva Dövlət Tibb Universiteti'], 'en' => ['name' => 'I.M. Sechenov First Moscow State Medical University']]],
            ['translates' => ['az' => ['name' => 'Sankt-Peterburq Dövlət Tibb Universiteti'], 'en' => ['name' => 'Saint Petersburg State Medical University']]],
            ['translates' => ['az' => ['name' => 'Hacettepe Universiteti'], 'en' => ['name' => 'Hacettepe University']]],
            ['translates' => ['az' => ['name' => 'İstanbul Universiteti'], 'en' => ['name' => 'Istanbul University']]],
            ['translates' => ['az' => ['name' => 'Ankara Universiteti'], 'en' => ['name' => 'Ankara University']]],
            ['translates' => ['az' => ['name' => 'Xarici ölkə universiteti'], 'en' => ['name' => 'Foreign university']]]
        ]);

        // Elmi dərəcə
        $academicDegree = $this->createAttribute([
            'translates' => [
                'az' => ['name' => 'Elmi dərəcə', 'description' => 'Həkimin elmi dərəcəsi'],
                'en' => ['name' => 'Academic degree', 'description' => 'Doctor\'s academic degree']
            ],
            'type' => AttributeTypeEnum::Select,
            'order' => 9
        ]);

        // Elmi dərəcə seçimləri
        $this->createAttributeOptions($academicDegree->id, [
            ['translates' => ['az' => ['name' => 'Bakalavr'], 'en' => ['name' => 'Bachelor']]],
            ['translates' => ['az' => ['name' => 'Magistr'], 'en' => ['name' => 'Master']]],
            ['translates' => ['az' => ['name' => 'Tibb üzrə fəlsəfə doktoru (PhD)'], 'en' => ['name' => 'Doctor of Philosophy in Medicine (PhD)']]],
            ['translates' => ['az' => ['name' => 'Tibb elmləri doktoru'], 'en' => ['name' => 'Doctor of Medical Sciences']]],
            ['translates' => ['az' => ['name' => 'Professor'], 'en' => ['name' => 'Professor']]],
            ['translates' => ['az' => ['name' => 'Akademik'], 'en' => ['name' => 'Academician']]]
        ]);

        // Təcrübə müddəti
        $experience = $this->createAttribute([
            'translates' => [
                'az' => ['name' => 'Təcrübə müddəti', 'description' => 'Həkimin iş təcrübəsi'],
                'en' => ['name' => 'Experience', 'description' => 'Doctor\'s work experience']
            ],
            'type' => AttributeTypeEnum::Select,
            'order' => 10
        ]);

        // Təcrübə müddəti seçimləri
        $this->createAttributeOptions($experience->id, [
            ['translates' => ['az' => ['name' => '1-3 il'], 'en' => ['name' => '1-3 years']]],
            ['translates' => ['az' => ['name' => '3-5 il'], 'en' => ['name' => '3-5 years']]],
            ['translates' => ['az' => ['name' => '5-10 il'], 'en' => ['name' => '5-10 years']]],
            ['translates' => ['az' => ['name' => '10-15 il'], 'en' => ['name' => '10-15 years']]],
            ['translates' => ['az' => ['name' => '15-20 il'], 'en' => ['name' => '15-20 years']]],
            ['translates' => ['az' => ['name' => '20+ il'], 'en' => ['name' => '20+ years']]]
        ]);

        // İxtisaslaşdığı sahələr
        $specializations = $this->createAttribute([
            'translates' => [
                'az' => ['name' => 'İxtisaslaşdığı sahələr', 'description' => 'Həkimin ixtisaslaşdığı tibbi sahələr'],
                'en' => ['name' => 'Specializations', 'description' => 'Medical areas doctor specializes in']
            ],
            'type' => AttributeTypeEnum::MultiSelect,
            'order' => 11
        ]);

        // Kateqoriyalardan asılı olaraq dəyişəcək, burada ümumi nümunələr verilib
        $this->createAttributeOptions($specializations->id, [
            ['translates' => ['az' => ['name' => 'Ürək xəstəlikləri'], 'en' => ['name' => 'Heart diseases']]],
            ['translates' => ['az' => ['name' => 'Qan-damar xəstəlikləri'], 'en' => ['name' => 'Vascular diseases']]],
            ['translates' => ['az' => ['name' => 'Endokrin xəstəliklər'], 'en' => ['name' => 'Endocrine disorders']]],
            ['translates' => ['az' => ['name' => 'Nevroloji xəstəliklər'], 'en' => ['name' => 'Neurological disorders']]],
            ['translates' => ['az' => ['name' => 'Onkoloji xəstəliklər'], 'en' => ['name' => 'Oncological diseases']]],
            ['translates' => ['az' => ['name' => 'Dəri xəstəlikləri'], 'en' => ['name' => 'Skin diseases']]],
            ['translates' => ['az' => ['name' => 'Allergiya'], 'en' => ['name' => 'Allergies']]],
            ['translates' => ['az' => ['name' => 'Uşaq xəstəlikləri'], 'en' => ['name' => 'Children\'s diseases']]],
            ['translates' => ['az' => ['name' => 'Qadın xəstəlikləri'], 'en' => ['name' => 'Women\'s health issues']]],
            ['translates' => ['az' => ['name' => 'Kişi xəstəlikləri'], 'en' => ['name' => 'Men\'s health issues']]],
            ['translates' => ['az' => ['name' => 'Yoluxucu xəstəliklər'], 'en' => ['name' => 'Infectious diseases']]],
            ['translates' => ['az' => ['name' => 'Mədə-bağırsaq xəstəlikləri'], 'en' => ['name' => 'Gastrointestinal diseases']]],
            ['translates' => ['az' => ['name' => 'Tənəffüs yolları xəstəlikləri'], 'en' => ['name' => 'Respiratory diseases']]],
            ['translates' => ['az' => ['name' => 'Sidik-cinsiyyət sistemi xəstəlikləri'], 'en' => ['name' => 'Urogenital system diseases']]],
            ['translates' => ['az' => ['name' => 'Göz xəstəlikləri'], 'en' => ['name' => 'Eye diseases']]],
            ['translates' => ['az' => ['name' => 'Qulaq-burun-boğaz xəstəlikləri'], 'en' => ['name' => 'ENT diseases']]],
            ['translates' => ['az' => ['name' => 'Stomatologiya'], 'en' => ['name' => 'Dentistry']]],
            ['translates' => ['az' => ['name' => 'Plastik cərrahiyyə'], 'en' => ['name' => 'Plastic surgery']]],
            ['translates' => ['az' => ['name' => 'Ortopediya'], 'en' => ['name' => 'Orthopedics']]],
            ['translates' => ['az' => ['name' => 'Travmatologiya'], 'en' => ['name' => 'Traumatology']]]
        ]);
    }

    /**
     * Həkimlərin iş rejimi və qəbul ilə bağlı atributlar
     */
    private function createDoctorScheduleAttributes(): void
    {
        // İş günləri
        $workDays = $this->createAttribute([
            'translates' => [
                'az' => ['name' => 'İş günləri', 'description' => 'Həkimin iş günləri'],
                'en' => ['name' => 'Working days', 'description' => 'Doctor\'s working days']
            ],
            'type' => AttributeTypeEnum::MultiSelect,
            'order' => 12
        ]);

        // İş günləri seçimləri
        $this->createAttributeOptions($workDays->id, [
            ['translates' => ['az' => ['name' => 'Bazar ertəsi'], 'en' => ['name' => 'Monday']]],
            ['translates' => ['az' => ['name' => 'Çərşənbə axşamı'], 'en' => ['name' => 'Tuesday']]],
            ['translates' => ['az' => ['name' => 'Çərşənbə'], 'en' => ['name' => 'Wednesday']]],
            ['translates' => ['az' => ['name' => 'Cümə axşamı'], 'en' => ['name' => 'Thursday']]],
            ['translates' => ['az' => ['name' => 'Cümə'], 'en' => ['name' => 'Friday']]],
            ['translates' => ['az' => ['name' => 'Şənbə'], 'en' => ['name' => 'Saturday']]],
            ['translates' => ['az' => ['name' => 'Bazar'], 'en' => ['name' => 'Sunday']]]
        ]);

        // İş saatları
        $workHours = $this->createAttribute([
            'translates' => [
                'az' => ['name' => 'İş saatları', 'description' => 'Həkimin iş saatları'],
                'en' => ['name' => 'Working hours', 'description' => 'Doctor\'s working hours']
            ],
            'type' => AttributeTypeEnum::Select,
            'order' => 13
        ]);

        // İş saatları seçimləri
        $this->createAttributeOptions($workHours->id, [
            ['translates' => ['az' => ['name' => 'Səhər (08:00-12:00)'], 'en' => ['name' => 'Morning (08:00-12:00)']]],
            ['translates' => ['az' => ['name' => 'Günorta (12:00-17:00)'], 'en' => ['name' => 'Afternoon (12:00-17:00)']]],
            ['translates' => ['az' => ['name' => 'Axşam (17:00-21:00)'], 'en' => ['name' => 'Evening (17:00-21:00)']]],
            ['translates' => ['az' => ['name' => 'Tam gün (08:00-21:00)'], 'en' => ['name' => 'Full day (08:00-21:00)']]],
            ['translates' => ['az' => ['name' => 'Gecə növbəsi (21:00-08:00)'], 'en' => ['name' => 'Night shift (21:00-08:00)']]]
        ]);

        // Qəbul forması
        $consultationFormat = $this->createAttribute([
            'translates' => [
                'az' => ['name' => 'Qəbul forması', 'description' => 'Həkimin qəbul forması'],
                'en' => ['name' => 'Consultation format', 'description' => 'Doctor\'s consultation format']
            ],
            'type' => AttributeTypeEnum::MultiSelect,
            'order' => 14
        ]);

        // Qəbul forması seçimləri
        $this->createAttributeOptions($consultationFormat->id, [
            ['translates' => ['az' => ['name' => 'Şəxsi qəbul'], 'en' => ['name' => 'In-person']]],
            ['translates' => ['az' => ['name' => 'Onlayn konsultasiya'], 'en' => ['name' => 'Online consultation']]],
            ['translates' => ['az' => ['name' => 'Evə çağırış'], 'en' => ['name' => 'Home visit']]]
        ]);

        // Təcili qəbul
        $urgentConsultation = $this->createAttribute([
            'translates' => [
                'az' => ['name' => 'Təcili qəbul', 'description' => 'Təcili qəbul imkanı'],
                'en' => ['name' => 'Urgent consultation', 'description' => 'Urgent consultation availability']
            ],
            'type' => AttributeTypeEnum::Boolean,
            'order' => 15
        ]);
    }

    /**
     * Həkimlərin xidmətləri ilə bağlı atributlar
     */
    private function createDoctorServicesAttributes(): void
    {
        // Xidmətlər
        $services = $this->createAttribute([
            'translates' => [
                'az' => ['name' => 'Xidmətlər', 'description' => 'Həkimin təklif etdiyi xidmətlər'],
                'en' => ['name' => 'Services', 'description' => 'Services offered by the doctor']
            ],
            'type' => AttributeTypeEnum::MultiSelect,
            'order' => 16
        ]);

        // Xidmətlər seçimləri - Ümumi xidmətlər, kateqoriyadan asılı olaraq dəyişəcək
        $this->createAttributeOptions($services->id, [
            ['translates' => ['az' => ['name' => 'Diaqnostika'], 'en' => ['name' => 'Diagnostics']]],
            ['translates' => ['az' => ['name' => 'Konsultasiya'], 'en' => ['name' => 'Consultation']]],
            ['translates' => ['az' => ['name' => 'Müalicə planının tərtib edilməsi'], 'en' => ['name' => 'Treatment plan development']]],
            ['translates' => ['az' => ['name' => 'Cərrahi əməliyyat'], 'en' => ['name' => 'Surgical operation']]],
            ['translates' => ['az' => ['name' => 'Müayinə'], 'en' => ['name' => 'Examination']]],
            ['translates' => ['az' => ['name' => 'Profilaktik baxış'], 'en' => ['name' => 'Preventive check-up']]],
            ['translates' => ['az' => ['name' => 'İkinci rəy'], 'en' => ['name' => 'Second opinion']]],
            ['translates' => ['az' => ['name' => 'Dispanser müşahidə'], 'en' => ['name' => 'Follow-up care']]],
            ['translates' => ['az' => ['name' => 'Erkən diaqnostika'], 'en' => ['name' => 'Early diagnostics']]],
            ['translates' => ['az' => ['name' => 'Reabilitasiya'], 'en' => ['name' => 'Rehabilitation']]]
        ]);

        // Qəbul müddəti
        $consultationDuration = $this->createAttribute([
            'translates' => [
                'az' => ['name' => 'Qəbul müddəti', 'description' => 'Həkimin orta qəbul müddəti'],
                'en' => ['name' => 'Consultation duration', 'description' => 'Average consultation duration']
            ],
            'type' => AttributeTypeEnum::Select,
            'order' => 17
        ]);

        // Qəbul müddəti seçimləri
        $this->createAttributeOptions($consultationDuration->id, [
            ['translates' => ['az' => ['name' => '15 dəqiqə'], 'en' => ['name' => '15 minutes']]],
            ['translates' => ['az' => ['name' => '30 dəqiqə'], 'en' => ['name' => '30 minutes']]],
            ['translates' => ['az' => ['name' => '45 dəqiqə'], 'en' => ['name' => '45 minutes']]],
            ['translates' => ['az' => ['name' => '60 dəqiqə'], 'en' => ['name' => '60 minutes']]],
            ['translates' => ['az' => ['name' => '90 dəqiqə'], 'en' => ['name' => '90 minutes']]]
        ]);

        // Xidmət paketləri
        $servicePackages = $this->createAttribute([
            'translates' => [
                'az' => ['name' => 'Xidmət paketləri', 'description' => 'Həkimin təklif etdiyi xidmət paketləri'],
                'en' => ['name' => 'Service packages', 'description' => 'Service packages offered by the doctor']
            ],
            'type' => AttributeTypeEnum::MultiSelect,
            'order' => 18
        ]);

        // Xidmət paketləri seçimləri
        $this->createAttributeOptions($servicePackages->id, [
            ['translates' => ['az' => ['name' => 'Əsas paket'], 'en' => ['name' => 'Basic package']]],
            ['translates' => ['az' => ['name' => 'Standart paket'], 'en' => ['name' => 'Standard package']]],
            ['translates' => ['az' => ['name' => 'Premium paket'], 'en' => ['name' => 'Premium package']]],
            ['translates' => ['az' => ['name' => 'Ailə paketi'], 'en' => ['name' => 'Family package']]],
            ['translates' => ['az' => ['name' => 'İllik profilaktik baxış paketi'], 'en' => ['name' => 'Annual preventive check-up package']]],
            ['translates' => ['az' => ['name' => 'Korporativ paket'], 'en' => ['name' => 'Corporate package']]]
        ]);

        // Klinika/Tibb mərkəzi
        $clinic = $this->createAttribute([
            'translates' => [
                'az' => ['name' => 'Klinika', 'description' => 'Həkimin çalışdığı klinika'],
                'en' => ['name' => 'Clinic', 'description' => 'Clinic where the doctor works']
            ],
            'type' => AttributeTypeEnum::Select,
            'order' => 19
        ]);

        // Klinika seçimləri - nümunə üçün, real layihədə dinamik ola bilər
        $this->createAttributeOptions($clinic->id, [
            ['translates' => ['az' => ['name' => 'Mərkəzi Klinika'], 'en' => ['name' => 'Central Clinic']]],
            ['translates' => ['az' => ['name' => 'MedEra Hospital'], 'en' => ['name' => 'MedEra Hospital']]],
            ['translates' => ['az' => ['name' => 'Bona Dea Beynəlxalq Hospital'], 'en' => ['name' => 'Bona Dea International Hospital']]],
            ['translates' => ['az' => ['name' => 'Memorial Klinika'], 'en' => ['name' => 'Memorial Clinic']]],
            ['translates' => ['az' => ['name' => 'Azərbaycan Tibb Universiteti Klinikası'], 'en' => ['name' => 'Azerbaijan Medical University Clinic']]],
            ['translates' => ['az' => ['name' => 'Leyla Medical Center'], 'en' => ['name' => 'Leyla Medical Center']]],
            ['translates' => ['az' => ['name' => 'Sağlam Ailə Tibb Mərkəzi'], 'en' => ['name' => 'Saglam Aile Medical Center']]],
            ['translates' => ['az' => ['name' => 'Xüsusi şəxsi təcrübə'], 'en' => ['name' => 'Private practice']]]
        ]);
    }

    /**
     * Həkimlərin lisenziya və sertifikatları ilə bağlı atributlar
     */
    private function createDoctorCertificationsAttributes(): void
    {
        // Lisenziya və sertifikatlar
        $certifications = $this->createAttribute([
            'translates' => [
                'az' => ['name' => 'Sertifikatlar', 'description' => 'Həkimin sertifikatları və lisenziyaları'],
                'en' => ['name' => 'Certifications', 'description' => 'Doctor\'s certifications and licenses']
            ],
            'type' => AttributeTypeEnum::MultiSelect,
            'order' => 20
        ]);

        // Sertifikat seçimləri
        $this->createAttributeOptions($certifications->id, [
            ['translates' => ['az' => ['name' => 'Azərbaycan Tibb Lisenziyası'], 'en' => ['name' => 'Azerbaijan Medical License']]],
            ['translates' => ['az' => ['name' => 'Beynəlxalq Sertifikat'], 'en' => ['name' => 'International Certification']]],
            ['translates' => ['az' => ['name' => 'Avropa Tibb Lisenziyası'], 'en' => ['name' => 'European Medical License']]],
            ['translates' => ['az' => ['name' => 'ABŞ Tibb Lisenziyası'], 'en' => ['name' => 'US Medical License']]],
            ['translates' => ['az' => ['name' => 'Tibbi Assosiasiya Üzvlüyü'], 'en' => ['name' => 'Medical Association Membership']]],
            ['translates' => ['az' => ['name' => 'İxtisaslaşma Sertifikatı'], 'en' => ['name' => 'Specialization Certificate']]],
            ['translates' => ['az' => ['name' => 'Elmi Dərəcə Diplomu'], 'en' => ['name' => 'Scientific Degree Diploma']]]
        ]);

        // Elmi işlər
        $scientificWorks = $this->createAttribute([
            'translates' => [
                'az' => ['name' => 'Elmi işlər', 'description' => 'Həkimin elmi işləri və nəşrləri'],
                'en' => ['name' => 'Scientific works', 'description' => 'Doctor\'s scientific works and publications']
            ],
            'type' => AttributeTypeEnum::Boolean,
            'order' => 21
        ]);

        // Tədris fəaliyyəti
        $teaching = $this->createAttribute([
            'translates' => [
                'az' => ['name' => 'Tədris fəaliyyəti', 'description' => 'Həkimin tədris fəaliyyəti'],
                'en' => ['name' => 'Teaching activity', 'description' => 'Doctor\'s teaching activity']
            ],
            'type' => AttributeTypeEnum::Boolean,
            'order' => 22
        ]);

        // Beynəlxalq təcrübə
        $internationalExperience = $this->createAttribute([
            'translates' => [
                'az' => ['name' => 'Beynəlxalq təcrübə', 'description' => 'Həkimin beynəlxalq təcrübəsi'],
                'en' => ['name' => 'International experience', 'description' => 'Doctor\'s international experience']
            ],
            'type' => AttributeTypeEnum::Boolean,
            'order' => 23
        ]);
    }

    /**
     * Ana attribute yaradır
     */
    private function createAttribute(array $data): Attribute
    {
        $faker = \Faker\Factory::create();
        return Attribute::create([
            ...$data,
            'group_name' => $faker->word
        ]);
    }

    /**
     * Attribute üçün options yaradır
     */
    private function createAttributeOptions(int $attributeId, array $options): void
    {
        foreach ($options as $index => $option) {
            $lastOrder = Attribute::find($attributeId)->options()->max('order') ?? 0;
            Attribute::find($attributeId)->options()->create([
                ...$option,
                'order' => $lastOrder + 1,
                'is_active' => true
            ]);
        }
    }
}
