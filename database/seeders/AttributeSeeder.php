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

        // Əmlak elanları üçün atributlar
        $this->createRealEstateAttributes();

        // Nəqliyyat elanları üçün atributlar
        $this->createVehicleAttributes();

        // Elektronika elanları üçün atributlar
        $this->createElectronicsAttributes();

        // Ev və bağ üçün atributlar
        $this->createHomeAndGardenAttributes();

        // İş elanları üçün atributlar
        $this->createJobAttributes();

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Əmlak elanları üçün atributlar
     */
    private function createRealEstateAttributes(): void
    {
        // Əsas mənzil məlumatları
        $mainInfo = $this->createAttribute([
            'translates' => [
                'az' => [
                    'name' => 'Əsas məlumatlar',
                    'description' => 'Mənzilin əsas məlumatları'
                ],
                'en' => [
                    'name' => 'Main Information',
                    'description' => 'Main apartment information'
                ]
            ],
            'type' => AttributeTypeEnum::Text,
            'order' => 1
        ]);

        // Otaq sayı
        $rooms = $this->createAttribute([
            'translates' => [
                'az' => ['name' => 'Otaq sayı', 'description' => 'Mənzildəki otaq sayı'],
                'en' => ['name' => 'Number of rooms', 'description' => 'Number of rooms in apartment']
            ],
            'type' => AttributeTypeEnum::Select,
            'order' => 2
        ]);

        // Otaq sayı üçün seçimlər
        $this->createAttributeOptions($rooms->id, [
            ['translates' => ['az' => ['name' => '1 otaqlı'], 'en' => ['name' => '1 room']]],
            ['translates' => ['az' => ['name' => '2 otaqlı'], 'en' => ['name' => '2 rooms']]],
            ['translates' => ['az' => ['name' => '3 otaqlı'], 'en' => ['name' => '3 rooms']]],
            ['translates' => ['az' => ['name' => '4 otaqlı'], 'en' => ['name' => '4 rooms']]],
            ['translates' => ['az' => ['name' => '5+ otaqlı'], 'en' => ['name' => '5+ rooms']]]
        ]);

        // Sahə (m²)
        $this->createAttribute([
            'translates' => [
                'az' => ['name' => 'Sahə', 'description' => 'Mənzilin ümumi sahəsi'],
                'en' => ['name' => 'Area', 'description' => 'Total area of apartment']
            ],
            'type' => AttributeTypeEnum::Integer,
            'order' => 3,
            'custom_fields' => [
                'suffix' => 'm²',
                'min' => 1,
                'max' => 1000
            ]
        ]);

        // Mərtəbə
        $floor = $this->createAttribute([
            'translates' => [
                'az' => ['name' => 'Mərtəbə', 'description' => 'Mənzilin yerləşdiyi mərtəbə'],
                'en' => ['name' => 'Floor', 'description' => 'Floor of apartment']
            ],
            'type' => AttributeTypeEnum::Integer,
            'order' => 4,
            'custom_fields' => [
                'min' => 1,
                'max' => 100
            ]
        ]);

        // Ümumi mərtəbə sayı
        $this->createAttribute([
            'translates' => [
                'az' => ['name' => 'Mərtəbə sayı', 'description' => 'Binanın ümumi mərtəbə sayı'],
                'en' => ['name' => 'Total floors', 'description' => 'Total number of floors in building']
            ],
            'type' => AttributeTypeEnum::Integer,
            'order' => 5
        ]);

        // Təmir
        $renovation = $this->createAttribute([
            'translates' => [
                'az' => ['name' => 'Təmir', 'description' => 'Mənzilin təmir vəziyyəti'],
                'en' => ['name' => 'Renovation', 'description' => 'Apartment renovation status']
            ],
            'type' => AttributeTypeEnum::Select,
            'order' => 6
        ]);

        // Təmir seçimləri
        $this->createAttributeOptions($renovation->id, [
            ['translates' => ['az' => ['name' => 'Təmirsiz'], 'en' => ['name' => 'No renovation']]],
            ['translates' => ['az' => ['name' => 'Natamam təmirli'], 'en' => ['name' => 'Partial renovation']]],
            ['translates' => ['az' => ['name' => 'Orta təmirli'], 'en' => ['name' => 'Average renovation']]],
            ['translates' => ['az' => ['name' => 'Əla təmirli'], 'en' => ['name' => 'Excellent renovation']]],
            ['translates' => ['az' => ['name' => 'Təmirli'], 'en' => ['name' => 'Renovated']]]
        ]);

        // Sənəd
        $document = $this->createAttribute([
            'translates' => [
                'az' => ['name' => 'Sənəd', 'description' => 'Mənzilin sənədləşməsi'],
                'en' => ['name' => 'Documents', 'description' => 'Property documents']
            ],
            'type' => AttributeTypeEnum::Select,
            'order' => 7
        ]);

        // Sənəd seçimləri
        $this->createAttributeOptions($document->id, [
            ['translates' => ['az' => ['name' => 'Kupça'], 'en' => ['name' => 'Ownership certificate']]],
            ['translates' => ['az' => ['name' => 'Müqavilə'], 'en' => ['name' => 'Contract']]],
            ['translates' => ['az' => ['name' => 'Sərəncam'], 'en' => ['name' => 'Order']]],
            ['translates' => ['az' => ['name' => 'Çıxarış'], 'en' => ['name' => 'Extract']]]
        ]);

        // Əlavə imkanlar
        $features = $this->createAttribute([
            'translates' => [
                'az' => ['name' => 'Əlavə imkanlar', 'description' => 'Mənzilin əlavə imkanları'],
                'en' => ['name' => 'Additional features', 'description' => 'Additional apartment features']
            ],
            'type' => AttributeTypeEnum::MultiSelect,
            'order' => 8
        ]);

        // Əlavə imkanlar seçimləri
        $this->createAttributeOptions($features->id, [
            ['translates' => ['az' => ['name' => 'Mebel'], 'en' => ['name' => 'Furniture']]],
            ['translates' => ['az' => ['name' => 'Mərkəzi istilik'], 'en' => ['name' => 'Central heating']]],
            ['translates' => ['az' => ['name' => 'Qaz'], 'en' => ['name' => 'Gas']]],
            ['translates' => ['az' => ['name' => 'Su'], 'en' => ['name' => 'Water']]],
            ['translates' => ['az' => ['name' => 'İşıq'], 'en' => ['name' => 'Electricity']]],
            ['translates' => ['az' => ['name' => 'Telefon'], 'en' => ['name' => 'Phone']]],
            ['translates' => ['az' => ['name' => 'Internet'], 'en' => ['name' => 'Internet']]],
            ['translates' => ['az' => ['name' => 'Kabel TV'], 'en' => ['name' => 'Cable TV']]],
            ['translates' => ['az' => ['name' => 'Kondisioner'], 'en' => ['name' => 'Air conditioning']]],
            ['translates' => ['az' => ['name' => 'Lift'], 'en' => ['name' => 'Elevator']]],
            ['translates' => ['az' => ['name' => 'Parking'], 'en' => ['name' => 'Parking']]],
            ['translates' => ['az' => ['name' => 'Qaraj'], 'en' => ['name' => 'Garage']]],
            ['translates' => ['az' => ['name' => 'Təhlükəsizlik'], 'en' => ['name' => 'Security']]],
            ['translates' => ['az' => ['name' => 'Uşaq meydançası'], 'en' => ['name' => 'Playground']]],
            ['translates' => ['az' => ['name' => 'Eyvan'], 'en' => ['name' => 'Balcony']]]
        ]);
    }

    /**
     * Nəqliyyat vasitələri üçün atributlar
     */
    private function createVehicleAttributes(): void
    {
        // Marka
        $brand = $this->createAttribute([
            'translates' => [
                'az' => ['name' => 'Marka', 'description' => 'Avtomobil markası'],
                'en' => ['name' => 'Brand', 'description' => 'Vehicle brand']
            ],
            'type' => AttributeTypeEnum::Select,
            'order' => 1
        ]);

        // Marka seçimləri (ən populyar markalar)
        $this->createAttributeOptions($brand->id, [
            ['translates' => ['az' => ['name' => 'Mercedes'], 'en' => ['name' => 'Mercedes']]],
            ['translates' => ['az' => ['name' => 'BMW'], 'en' => ['name' => 'BMW']]],
            ['translates' => ['az' => ['name' => 'Toyota'], 'en' => ['name' => 'Toyota']]],
            ['translates' => ['az' => ['name' => 'Lexus'], 'en' => ['name' => 'Lexus']]],
            ['translates' => ['az' => ['name' => 'Hyundai'], 'en' => ['name' => 'Hyundai']]],
            ['translates' => ['az' => ['name' => 'Kia'], 'en' => ['name' => 'Kia']]],
            ['translates' => ['az' => ['name' => 'Ford'], 'en' => ['name' => 'Ford']]],
            ['translates' => ['az' => ['name' => 'Chevrolet'], 'en' => ['name' => 'Chevrolet']]],
            ['translates' => ['az' => ['name' => 'Nissan'], 'en' => ['name' => 'Nissan']]],
            ['translates' => ['az' => ['name' => 'Honda'], 'en' => ['name' => 'Honda']]],
            ['translates' => ['az' => ['name' => 'Volkswagen'], 'en' => ['name' => 'Volkswagen']]],
            ['translates' => ['az' => ['name' => 'Audi'], 'en' => ['name' => 'Audi']]]
        ]);

        // Model (marka seçiləndən sonra gələcək)
        $model = $this->createAttribute([
            'translates' => [
                'az' => ['name' => 'Model', 'description' => 'Avtomobil modeli'],
                'en' => ['name' => 'Model', 'description' => 'Vehicle model']
            ],
            'type' => AttributeTypeEnum::Select,
            'parent_id' => $brand->id,
            'order' => 2
        ]);

        // Buraya model options əlavə etmirik çünki dinamik olacaq

        // Ban növü
        $bodyType = $this->createAttribute([
            'translates' => [
                'az' => ['name' => 'Ban növü', 'description' => 'Avtomobilin ban növü'],
                'en' => ['name' => 'Body type', 'description' => 'Vehicle body type']
            ],
            'type' => AttributeTypeEnum::Select,
            'order' => 3
        ]);

        // Ban növü seçimləri
        $this->createAttributeOptions($bodyType->id, [
            ['translates' => ['az' => ['name' => 'Sedan'], 'en' => ['name' => 'Sedan']]],
            ['translates' => ['az' => ['name' => 'SUV'], 'en' => ['name' => 'SUV']]],
            ['translates' => ['az' => ['name' => 'Hetçbek'], 'en' => ['name' => 'Hatchback']]],
            ['translates' => ['az' => ['name' => 'Universal'], 'en' => ['name' => 'Wagon']]],
            ['translates' => ['az' => ['name' => 'Kupe'], 'en' => ['name' => 'Coupe']]],
            ['translates' => ['az' => ['name' => 'Pikap'], 'en' => ['name' => 'Pickup']]],
            ['translates' => ['az' => ['name' => 'Minivan'], 'en' => ['name' => 'Minivan']]],
            ['translates' => ['az' => ['name' => 'Kabriolet'], 'en' => ['name' => 'Convertible']]]
        ]);

        // Buraxılış ili
        $this->createAttribute([
            'translates' => [
                'az' => ['name' => 'Buraxılış ili', 'description' => 'Avtomobilin istehsal ili'],
                'en' => ['name' => 'Year', 'description' => 'Manufacturing year']
            ],
            'type' => AttributeTypeEnum::Year,
            'order' => 4,
            'custom_fields' => [
                'min_year' => 1960,
                'max_year' => date('Y') // Cari il
            ]
        ]);

        // Mühərrik həcmi
        $this->createAttribute([
            'translates' => [
                'az' => ['name' => 'Mühərrik həcmi', 'description' => 'Mühərrikin həcmi'],
                'en' => ['name' => 'Engine size', 'description' => 'Engine displacement']
            ],
            'type' => AttributeTypeEnum::Decimal,
            'order' => 5,
            'custom_fields' => [
                'min' => 0.5,
                'max' => 9.9,
                'step' => 0.1,
                'suffix' => 'L'
            ]
        ]);

        // Mühərrik gücü
        $this->createAttribute([
            'translates' => [
                'az' => ['name' => 'Mühərrik gücü', 'description' => 'Mühərrik gücü (at gücü)'],
                'en' => ['name' => 'Engine power', 'description' => 'Engine power (horsepower)']
            ],
            'type' => AttributeTypeEnum::Integer,
            'order' => 6,
            'custom_fields' => [
                'min' => 1,
                'max' => 2000,
                'suffix' => 'a.g.'
            ]
        ]);

        // Yanacaq növü
        $fuelType = $this->createAttribute([
            'translates' => [
                'az' => ['name' => 'Yanacaq növü', 'description' => 'İstifadə edilən yanacaq növü'],
                'en' => ['name' => 'Fuel type', 'description' => 'Type of fuel used']
            ],
            'type' => AttributeTypeEnum::Select,
            'order' => 7
        ]);

        // Yanacaq növü seçimləri
        $this->createAttributeOptions($fuelType->id, [
            ['translates' => ['az' => ['name' => 'Benzin'], 'en' => ['name' => 'Gasoline']]],
            ['translates' => ['az' => ['name' => 'Dizel'], 'en' => ['name' => 'Diesel']]],
            ['translates' => ['az' => ['name' => 'Qaz'], 'en' => ['name' => 'Gas']]],
            ['translates' => ['az' => ['name' => 'Elektrik'], 'en' => ['name' => 'Electric']]],
            ['translates' => ['az' => ['name' => 'Hibrid'], 'en' => ['name' => 'Hybrid']]]
        ]);

        // Sürətlər qutusu
        $transmission = $this->createAttribute([
            'translates' => [
                'az' => ['name' => 'Sürətlər qutusu', 'description' => 'Sürətlər qutusunun növü'],
                'en' => ['name' => 'Transmission', 'description' => 'Type of transmission']
            ],
            'type' => AttributeTypeEnum::Select,
            'order' => 8
        ]);

        // Sürətlər qutusu seçimləri
        $this->createAttributeOptions($transmission->id, [
            ['translates' => ['az' => ['name' => 'Mexaniki'], 'en' => ['name' => 'Manual']]],
            ['translates' => ['az' => ['name' => 'Avtomat'], 'en' => ['name' => 'Automatic']]],
            ['translates' => ['az' => ['name' => 'Robotlaşdırılmış'], 'en' => ['name' => 'Automated']]],
            ['translates' => ['az' => ['name' => 'Variator'], 'en' => ['name' => 'CVT']]]
        ]);

        // Ötürücü
        $drivetrain = $this->createAttribute([
            'translates' => [
                'az' => ['name' => 'Ötürücü', 'description' => 'Ötürücünün növü'],
                'en' => ['name' => 'Drivetrain', 'description' => 'Type of drivetrain']
            ],
            'type' => AttributeTypeEnum::Select,
            'order' => 9
        ]);

        // Ötürücü seçimləri
        $this->createAttributeOptions($drivetrain->id, [
            ['translates' => ['az' => ['name' => 'Arxa'], 'en' => ['name' => 'Rear wheel']]],
            ['translates' => ['az' => ['name' => 'Ön'], 'en' => ['name' => 'Front wheel']]],
            ['translates' => ['az' => ['name' => 'Tam'], 'en' => ['name' => 'All wheel']]]
        ]);

        // Yürüş
        $this->createAttribute([
            'translates' => [
                'az' => ['name' => 'Yürüş', 'description' => 'Avtomobilin yürüşü'],
                'en' => ['name' => 'Mileage', 'description' => 'Vehicle mileage']
            ],
            'type' => AttributeTypeEnum::Integer,
            'order' => 10,
            'custom_fields' => [
                'min' => 0,
                'max' => 999999,
                'step' => 1000,
                'suffix' => 'km'
            ]
        ]);

        // Rəng
        $color = $this->createAttribute([
            'translates' => [
                'az' => ['name' => 'Rəng', 'description' => 'Avtomobilin rəngi'],
                'en' => ['name' => 'Color', 'description' => 'Vehicle color']
            ],
            'type' => AttributeTypeEnum::Select,
            'order' => 11
        ]);

        // Rəng seçimləri
        $this->createAttributeOptions($color->id, [
            ['translates' => ['az' => ['name' => 'Ağ'], 'en' => ['name' => 'White']], 'custom_fields' => ['color' => '#FFFFFF']],
            ['translates' => ['az' => ['name' => 'Qara'], 'en' => ['name' => 'Black']], 'custom_fields' => ['color' => '#000000']],
            ['translates' => ['az' => ['name' => 'Gümüşü'], 'en' => ['name' => 'Silver']], 'custom_fields' => ['color' => '#C0C0C0']],
            ['translates' => ['az' => ['name' => 'Boz'], 'en' => ['name' => 'Gray']], 'custom_fields' => ['color' => '#808080']],
            ['translates' => ['az' => ['name' => 'Qırmızı'], 'en' => ['name' => 'Red']], 'custom_fields' => ['color' => '#FF0000']],
            ['translates' => ['az' => ['name' => 'Göy'], 'en' => ['name' => 'Blue']], 'custom_fields' => ['color' => '#0000FF']],
            ['translates' => ['az' => ['name' => 'Yaşıl'], 'en' => ['name' => 'Green']], 'custom_fields' => ['color' => '#008000']],
            ['translates' => ['az' => ['name' => 'Bej'], 'en' => ['name' => 'Beige']], 'custom_fields' => ['color' => '#F5F5DC']],
            ['translates' => ['az' => ['name' => 'Qəhvəyi'], 'en' => ['name' => 'Brown']], 'custom_fields' => ['color' => '#A52A2A']],
            ['translates' => ['az' => ['name' => 'Narıncı'], 'en' => ['name' => 'Orange']], 'custom_fields' => ['color' => '#FFA500']],
            ['translates' => ['az' => ['name' => 'Sarı'], 'en' => ['name' => 'Yellow']], 'custom_fields' => ['color' => '#FFFF00']],
            ['translates' => ['az' => ['name' => 'Bənövşəyi'], 'en' => ['name' => 'Purple']], 'custom_fields' => ['color' => '#800080']],
            ['translates' => ['az' => ['name' => 'Çəhrayı'], 'en' => ['name' => 'Pink']], 'custom_fields' => ['color' => '#FFC0CB']]
        ]);

        // Əlavə təchizatlar
        $features = $this->createAttribute([
            'translates' => [
                'az' => ['name' => 'Təchizat', 'description' => 'Avtomobilin təchizatı'],
                'en' => ['name' => 'Features', 'description' => 'Vehicle features']
            ],
            'type' => AttributeTypeEnum::MultiSelect,
            'order' => 11
        ]);

        // Təchizat seçimləri
        $this->createAttributeOptions($features->id, [
            // Təhlükəsizlik
            ['translates' => ['az' => ['name' => 'ABS'], 'en' => ['name' => 'ABS']]],
            ['translates' => ['az' => ['name' => 'Təhlükəsizlik yastıqları'], 'en' => ['name' => 'Airbags']]],
            ['translates' => ['az' => ['name' => 'ESP'], 'en' => ['name' => 'ESP']]],
            ['translates' => ['az' => ['name' => 'Kamera'], 'en' => ['name' => 'Camera']]],
            ['translates' => ['az' => ['name' => 'Park radarı'], 'en' => ['name' => 'Parking sensors']]],
            ['translates' => ['az' => ['name' => 'Kruiz kontrol'], 'en' => ['name' => 'Cruise control']]],
            ['translates' => ['az' => ['name' => 'Yağış sensoru'], 'en' => ['name' => 'Rain sensor']]],
            ['translates' => ['az' => ['name' => 'İşıq sensoru'], 'en' => ['name' => 'Light sensor']]],

            // Rahatlıq
            ['translates' => ['az' => ['name' => 'Kondisioner'], 'en' => ['name' => 'Air conditioning']]],
            ['translates' => ['az' => ['name' => 'Klimat kontrol'], 'en' => ['name' => 'Climate control']]],
            ['translates' => ['az' => ['name' => 'Dəri salon'], 'en' => ['name' => 'Leather interior']]],
            ['translates' => ['az' => ['name' => 'Oturacaqların isidilməsi'], 'en' => ['name' => 'Heated seats']]],
            ['translates' => ['az' => ['name' => 'Oturacaqların ventilyasiyası'], 'en' => ['name' => 'Ventilated seats']]],
            ['translates' => ['az' => ['name' => 'Elektrik oturacaqlar'], 'en' => ['name' => 'Power seats']]],
            ['translates' => ['az' => ['name' => 'Elektrik güzgülər'], 'en' => ['name' => 'Power mirrors']]],
            ['translates' => ['az' => ['name' => 'Mərkəzi qapanma'], 'en' => ['name' => 'Central locking']]],
            ['translates' => ['az' => ['name' => 'Multi sükan'], 'en' => ['name' => 'Multifunction steering wheel']]],
            ['translates' => ['az' => ['name' => 'Start-Stop düyməsi'], 'en' => ['name' => 'Start-Stop button']]],

            // Multimedia
            ['translates' => ['az' => ['name' => 'Audio sistem'], 'en' => ['name' => 'Audio system']]],
            ['translates' => ['az' => ['name' => 'AUX'], 'en' => ['name' => 'AUX']]],
            ['translates' => ['az' => ['name' => 'USB'], 'en' => ['name' => 'USB']]],
            ['translates' => ['az' => ['name' => 'Bluetooth'], 'en' => ['name' => 'Bluetooth']]],
            ['translates' => ['az' => ['name' => 'Navigation sistemi'], 'en' => ['name' => 'Navigation system']]],

            // Digər
            ['translates' => ['az' => ['name' => 'Lyuk'], 'en' => ['name' => 'Sunroof']]],
            ['translates' => ['az' => ['name' => 'Panorama dam'], 'en' => ['name' => 'Panoramic roof']]],
            ['translates' => ['az' => ['name' => 'Yüngül lehimli disklər'], 'en' => ['name' => 'Alloy wheels']]],
            ['translates' => ['az' => ['name' => 'Yan pərdələr'], 'en' => ['name' => 'Side curtains']]],
            ['translates' => ['az' => ['name' => 'Arxa görüntü kamerası'], 'en' => ['name' => 'Rear view camera']]]
        ]);

    }

    /**
     * Elektronika elanları üçün atributlar
     */
    private function createElectronicsAttributes(): void
    {
        // Məhsulun vəziyyəti
        $condition = $this->createAttribute([
            'translates' => [
                'az' => ['name' => 'Məhsulun vəziyyəti', 'description' => 'Məhsulun istifadə vəziyyəti'],
                'en' => ['name' => 'Product condition', 'description' => 'Usage condition of the product']
            ],
            'type' => AttributeTypeEnum::Select,
            'order' => 1
        ]);

        // Vəziyyət seçimləri
        $this->createAttributeOptions($condition->id, [
            ['translates' => ['az' => ['name' => 'Yeni'], 'en' => ['name' => 'New']]],
            ['translates' => ['az' => ['name' => 'Yeni kimidir'], 'en' => ['name' => 'Like new']]],
            ['translates' => ['az' => ['name' => 'Əla'], 'en' => ['name' => 'Excellent']]],
            ['translates' => ['az' => ['name' => 'Yaxşı'], 'en' => ['name' => 'Good']]],
            ['translates' => ['az' => ['name' => 'Kafi'], 'en' => ['name' => 'Fair']]],
            ['translates' => ['az' => ['name' => 'Təmirə ehtiyacı var'], 'en' => ['name' => 'Needs repair']]]
        ]);

        // Brend
        $brand = $this->createAttribute([
            'translates' => [
                'az' => ['name' => 'Brend', 'description' => 'Məhsulun brendi'],
                'en' => ['name' => 'Brand', 'description' => 'Product brand']
            ],
            'type' => AttributeTypeEnum::Select,
            'order' => 2
        ]);

        // Brend seçimləri (telefonlar üçün)
        $this->createAttributeOptions($brand->id, [
            ['translates' => ['az' => ['name' => 'Apple'], 'en' => ['name' => 'Apple']]],
            ['translates' => ['az' => ['name' => 'Samsung'], 'en' => ['name' => 'Samsung']]],
            ['translates' => ['az' => ['name' => 'Xiaomi'], 'en' => ['name' => 'Xiaomi']]],
            ['translates' => ['az' => ['name' => 'Huawei'], 'en' => ['name' => 'Huawei']]],
            ['translates' => ['az' => ['name' => 'OnePlus'], 'en' => ['name' => 'OnePlus']]],
            ['translates' => ['az' => ['name' => 'Google'], 'en' => ['name' => 'Google']]],
            ['translates' => ['az' => ['name' => 'Sony'], 'en' => ['name' => 'Sony']]],
            ['translates' => ['az' => ['name' => 'LG'], 'en' => ['name' => 'LG']]],
            ['translates' => ['az' => ['name' => 'Nokia'], 'en' => ['name' => 'Nokia']]],
            ['translates' => ['az' => ['name' => 'Motorola'], 'en' => ['name' => 'Motorola']]]
        ]);

        // Model (brendə bağlı)
        $model = $this->createAttribute([
            'translates' => [
                'az' => ['name' => 'Model', 'description' => 'Məhsulun modeli'],
                'en' => ['name' => 'Model', 'description' => 'Product model']
            ],
            'type' => AttributeTypeEnum::Select,
            'parent_id' => $brand->id,
            'order' => 3
        ]);

        // Yaddaş həcmi
        $storage = $this->createAttribute([
            'translates' => [
                'az' => ['name' => 'Yaddaş', 'description' => 'Daxili yaddaş həcmi'],
                'en' => ['name' => 'Storage', 'description' => 'Internal storage capacity']
            ],
            'type' => AttributeTypeEnum::Select,
            'order' => 4
        ]);

        // Yaddaş seçimləri
        $this->createAttributeOptions($storage->id, [
            ['translates' => ['az' => ['name' => '16 GB'], 'en' => ['name' => '16 GB']]],
            ['translates' => ['az' => ['name' => '32 GB'], 'en' => ['name' => '32 GB']]],
            ['translates' => ['az' => ['name' => '64 GB'], 'en' => ['name' => '64 GB']]],
            ['translates' => ['az' => ['name' => '128 GB'], 'en' => ['name' => '128 GB']]],
            ['translates' => ['az' => ['name' => '256 GB'], 'en' => ['name' => '256 GB']]],
            ['translates' => ['az' => ['name' => '512 GB'], 'en' => ['name' => '512 GB']]],
            ['translates' => ['az' => ['name' => '1 TB'], 'en' => ['name' => '1 TB']]]
        ]);

        // RAM
        $ram = $this->createAttribute([
            'translates' => [
                'az' => ['name' => 'Operativ yaddaş', 'description' => 'RAM həcmi'],
                'en' => ['name' => 'RAM', 'description' => 'RAM capacity']
            ],
            'type' => AttributeTypeEnum::Select,
            'order' => 5
        ]);

        // RAM seçimləri
        $this->createAttributeOptions($ram->id, [
            ['translates' => ['az' => ['name' => '2 GB'], 'en' => ['name' => '2 GB']]],
            ['translates' => ['az' => ['name' => '3 GB'], 'en' => ['name' => '3 GB']]],
            ['translates' => ['az' => ['name' => '4 GB'], 'en' => ['name' => '4 GB']]],
            ['translates' => ['az' => ['name' => '6 GB'], 'en' => ['name' => '6 GB']]],
            ['translates' => ['az' => ['name' => '8 GB'], 'en' => ['name' => '8 GB']]],
            ['translates' => ['az' => ['name' => '12 GB'], 'en' => ['name' => '12 GB']]],
            ['translates' => ['az' => ['name' => '16 GB'], 'en' => ['name' => '16 GB']]]
        ]);

        // Rəng
        $color = $this->createAttribute([
            'translates' => [
                'az' => ['name' => 'Rəng', 'description' => 'Məhsulun rəngi'],
                'en' => ['name' => 'Color', 'description' => 'Product color']
            ],
            'type' => AttributeTypeEnum::Select,
            'order' => 6
        ]);

        // Rəng seçimləri
        $this->createAttributeOptions($color->id, [
            ['translates' => ['az' => ['name' => 'Qara'], 'en' => ['name' => 'Black']], 'custom_fields' => ['color' => '#000000']],
            ['translates' => ['az' => ['name' => 'Ağ'], 'en' => ['name' => 'White']], 'custom_fields' => ['color' => '#FFFFFF']],
            ['translates' => ['az' => ['name' => 'Boz'], 'en' => ['name' => 'Gray']], 'custom_fields' => ['color' => '#808080']],
            ['translates' => ['az' => ['name' => 'Qızılı'], 'en' => ['name' => 'Gold']], 'custom_fields' => ['color' => '#FFD700']],
            ['translates' => ['az' => ['name' => 'Gümüşü'], 'en' => ['name' => 'Silver']], 'custom_fields' => ['color' => '#C0C0C0']],
            ['translates' => ['az' => ['name' => 'Göy'], 'en' => ['name' => 'Blue']], 'custom_fields' => ['color' => '#0000FF']],
            ['translates' => ['az' => ['name' => 'Qırmızı'], 'en' => ['name' => 'Red']], 'custom_fields' => ['color' => '#FF0000']]
        ]);
        // Elektronika üçün əlavə xüsusiyyətlər
        $features = $this->createAttribute([
            'translates' => [
                'az' => ['name' => 'Xüsusiyyətlər', 'description' => 'Cihazın xüsusiyyətləri'],
                'en' => ['name' => 'Features', 'description' => 'Device features']
            ],
            'type' => AttributeTypeEnum::MultiSelect,
            'order' => 7
        ]);

        // Xüsusiyyətlər üçün seçimlər
        $this->createAttributeOptions($features->id, [
            // Ümumi xüsusiyyətlər
            ['translates' => ['az' => ['name' => '4G/LTE'], 'en' => ['name' => '4G/LTE']]],
            ['translates' => ['az' => ['name' => '5G'], 'en' => ['name' => '5G']]],
            ['translates' => ['az' => ['name' => 'WiFi'], 'en' => ['name' => 'WiFi']]],
            ['translates' => ['az' => ['name' => 'Bluetooth'], 'en' => ['name' => 'Bluetooth']]],
            ['translates' => ['az' => ['name' => 'NFC'], 'en' => ['name' => 'NFC']]],
            ['translates' => ['az' => ['name' => 'GPS'], 'en' => ['name' => 'GPS']]],
            ['translates' => ['az' => ['name' => 'Barmaq izi'], 'en' => ['name' => 'Fingerprint']]],
            ['translates' => ['az' => ['name' => 'Face ID'], 'en' => ['name' => 'Face ID']]],
            ['translates' => ['az' => ['name' => 'Simsiz şarj'], 'en' => ['name' => 'Wireless charging']]],
            ['translates' => ['az' => ['name' => 'Tez şarj'], 'en' => ['name' => 'Fast charging']]]
        ]);

        // Zəmanət
        $warranty = $this->createAttribute([
            'translates' => [
                'az' => ['name' => 'Zəmanət', 'description' => 'Zəmanət müddəti'],
                'en' => ['name' => 'Warranty', 'description' => 'Warranty period']
            ],
            'type' => AttributeTypeEnum::Select,
            'order' => 8
        ]);

        // Zəmanət seçimləri
        $this->createAttributeOptions($warranty->id, [
            ['translates' => ['az' => ['name' => 'Yoxdur'], 'en' => ['name' => 'No warranty']]],
            ['translates' => ['az' => ['name' => '6 ay'], 'en' => ['name' => '6 months']]],
            ['translates' => ['az' => ['name' => '1 il'], 'en' => ['name' => '1 year']]],
            ['translates' => ['az' => ['name' => '2 il'], 'en' => ['name' => '2 years']]]
        ]);
    }

    /**
     * Ev və bağ üçün atributlar
     */
    private function createHomeAndGardenAttributes(): void
    {
        // Məhsulun tipi
        $type = $this->createAttribute([
            'translates' => [
                'az' => ['name' => 'Məhsulun tipi', 'description' => 'Məişət texnikasının tipi'],
                'en' => ['name' => 'Product type', 'description' => 'Type of home appliance']
            ],
            'type' => AttributeTypeEnum::Select,
            'order' => 1
        ]);

        // Məhsul tipləri
        $this->createAttributeOptions($type->id, [
            // Böyük məişət texnikası
            ['translates' => ['az' => ['name' => 'Soyuducu'], 'en' => ['name' => 'Refrigerator']]],
            ['translates' => ['az' => ['name' => 'Paltaryuyan'], 'en' => ['name' => 'Washing machine']]],
            ['translates' => ['az' => ['name' => 'Qabyuyan'], 'en' => ['name' => 'Dishwasher']]],
            ['translates' => ['az' => ['name' => 'Plitə'], 'en' => ['name' => 'Stove']]],
            ['translates' => ['az' => ['name' => 'Soba'], 'en' => ['name' => 'Oven']]],
            ['translates' => ['az' => ['name' => 'Mikrodalğalı soba'], 'en' => ['name' => 'Microwave']]],

            // Kiçik məişət texnikası
            ['translates' => ['az' => ['name' => 'Toster'], 'en' => ['name' => 'Toaster']]],
            ['translates' => ['az' => ['name' => 'Blender'], 'en' => ['name' => 'Blender']]],
            ['translates' => ['az' => ['name' => 'Çaydan'], 'en' => ['name' => 'Kettle']]],
            ['translates' => ['az' => ['name' => 'Qəhvə maşını'], 'en' => ['name' => 'Coffee machine']]],
            ['translates' => ['az' => ['name' => 'Ət çəkən'], 'en' => ['name' => 'Meat grinder']]],

            // Komfort və iqlim texnikası
            ['translates' => ['az' => ['name' => 'Kondisioner'], 'en' => ['name' => 'Air conditioner']]],
            ['translates' => ['az' => ['name' => 'Ventilyator'], 'en' => ['name' => 'Fan']]],
            ['translates' => ['az' => ['name' => 'Hava təmizləyici'], 'en' => ['name' => 'Air purifier']]],
            ['translates' => ['az' => ['name' => 'İsidici'], 'en' => ['name' => 'Heater']]]
        ]);

        // Brend
        $brand = $this->createAttribute([
            'translates' => [
                'az' => ['name' => 'Brend', 'description' => 'Məişət texnikasının brendi'],
                'en' => ['name' => 'Brand', 'description' => 'Home appliance brand']
            ],
            'type' => AttributeTypeEnum::Select,
            'order' => 2
        ]);

        // Məişət texnikası brendləri
        $this->createAttributeOptions($brand->id, [
            ['translates' => ['az' => ['name' => 'Samsung'], 'en' => ['name' => 'Samsung']]],
            ['translates' => ['az' => ['name' => 'LG'], 'en' => ['name' => 'LG']]],
            ['translates' => ['az' => ['name' => 'Bosch'], 'en' => ['name' => 'Bosch']]],
            ['translates' => ['az' => ['name' => 'Siemens'], 'en' => ['name' => 'Siemens']]],
            ['translates' => ['az' => ['name' => 'Electrolux'], 'en' => ['name' => 'Electrolux']]],
            ['translates' => ['az' => ['name' => 'Arçelik'], 'en' => ['name' => 'Arcelik']]],
            ['translates' => ['az' => ['name' => 'Beko'], 'en' => ['name' => 'Beko']]],
            ['translates' => ['az' => ['name' => 'Vestel'], 'en' => ['name' => 'Vestel']]],
            ['translates' => ['az' => ['name' => 'Panasonic'], 'en' => ['name' => 'Panasonic']]],
            ['translates' => ['az' => ['name' => 'Philips'], 'en' => ['name' => 'Philips']]]
        ]);

        // Məhsulun vəziyyəti
        $condition = $this->createAttribute([
            'translates' => [
                'az' => ['name' => 'Vəziyyəti', 'description' => 'Məhsulun vəziyyəti'],
                'en' => ['name' => 'Condition', 'description' => 'Product condition']
            ],
            'type' => AttributeTypeEnum::Select,
            'order' => 3
        ]);

        // Vəziyyət seçimləri
        $this->createAttributeOptions($condition->id, [
            ['translates' => ['az' => ['name' => 'Yeni'], 'en' => ['name' => 'New']]],
            ['translates' => ['az' => ['name' => 'Yeni kimidir'], 'en' => ['name' => 'Like new']]],
            ['translates' => ['az' => ['name' => 'Yaxşı'], 'en' => ['name' => 'Good']]],
            ['translates' => ['az' => ['name' => 'Əla'], 'en' => ['name' => 'Excellent']]],
            ['translates' => ['az' => ['name' => 'Orta'], 'en' => ['name' => 'Fair']]],
            ['translates' => ['az' => ['name' => 'Təmirə ehtiyacı var'], 'en' => ['name' => 'Needs repair']]]
        ]);

        // Enerji səmərəliliyi
        $energyClass = $this->createAttribute([
            'translates' => [
                'az' => ['name' => 'Enerji sinfi', 'description' => 'Məhsulun enerji səmərəliliyi sinfi'],
                'en' => ['name' => 'Energy class', 'description' => 'Energy efficiency class']
            ],
            'type' => AttributeTypeEnum::Select,
            'order' => 4
        ]);

        // Enerji sinfi seçimləri
        $this->createAttributeOptions($energyClass->id, [
            ['translates' => ['az' => ['name' => 'A+++'], 'en' => ['name' => 'A+++']], 'custom_fields' => ['color' => '#00A651']],
            ['translates' => ['az' => ['name' => 'A++'], 'en' => ['name' => 'A++']], 'custom_fields' => ['color' => '#50B848']],
            ['translates' => ['az' => ['name' => 'A+'], 'en' => ['name' => 'A+']], 'custom_fields' => ['color' => '#B7D333']],
            ['translates' => ['az' => ['name' => 'A'], 'en' => ['name' => 'A']], 'custom_fields' => ['color' => '#FFED00']],
            ['translates' => ['az' => ['name' => 'B'], 'en' => ['name' => 'B']], 'custom_fields' => ['color' => '#FBB034']],
            ['translates' => ['az' => ['name' => 'C'], 'en' => ['name' => 'C']], 'custom_fields' => ['color' => '#F78C40']],
            ['translates' => ['az' => ['name' => 'D'], 'en' => ['name' => 'D']], 'custom_fields' => ['color' => '#EF4136']]
        ]);

        // Rəng
        $color = $this->createAttribute([
            'translates' => [
                'az' => ['name' => 'Rəng', 'description' => 'Məhsulun rəngi'],
                'en' => ['name' => 'Color', 'description' => 'Product color']
            ],
            'type' => AttributeTypeEnum::Select,
            'order' => 5
        ]);

        // Rəng seçimləri
        $this->createAttributeOptions($color->id, [
            ['translates' => ['az' => ['name' => 'Ağ'], 'en' => ['name' => 'White']], 'custom_fields' => ['color' => '#FFFFFF']],
            ['translates' => ['az' => ['name' => 'Gümüşü'], 'en' => ['name' => 'Silver']], 'custom_fields' => ['color' => '#C0C0C0']],
            ['translates' => ['az' => ['name' => 'Boz'], 'en' => ['name' => 'Gray']], 'custom_fields' => ['color' => '#808080']],
            ['translates' => ['az' => ['name' => 'Qara'], 'en' => ['name' => 'Black']], 'custom_fields' => ['color' => '#000000']],
            ['translates' => ['az' => ['name' => 'Qəhvəyi'], 'en' => ['name' => 'Brown']], 'custom_fields' => ['color' => '#A52A2A']],
            ['translates' => ['az' => ['name' => 'Bej'], 'en' => ['name' => 'Beige']], 'custom_fields' => ['color' => '#F5F5DC']]
        ]);

        // İstehsal ölkəsi
        $country = $this->createAttribute([
            'translates' => [
                'az' => ['name' => 'İstehsal ölkəsi', 'description' => 'Məhsulun istehsal olunduğu ölkə'],
                'en' => ['name' => 'Country of origin', 'description' => 'Manufacturing country']
            ],
            'type' => AttributeTypeEnum::Select,
            'order' => 6
        ]);

        // Ölkə seçimləri
        $this->createAttributeOptions($country->id, [
            ['translates' => ['az' => ['name' => 'Türkiyə'], 'en' => ['name' => 'Turkey']]],
            ['translates' => ['az' => ['name' => 'Almaniya'], 'en' => ['name' => 'Germany']]],
            ['translates' => ['az' => ['name' => 'Çin'], 'en' => ['name' => 'China']]],
            ['translates' => ['az' => ['name' => 'Yaponiya'], 'en' => ['name' => 'Japan']]],
            ['translates' => ['az' => ['name' => 'Cənubi Koreya'], 'en' => ['name' => 'South Korea']]],
            ['translates' => ['az' => ['name' => 'İtaliya'], 'en' => ['name' => 'Italy']]]
        ]);
    }

    /**
     * İş elanları üçün atributlar
     */
    private function createJobAttributes(): void
    {
        // İş növü
        $jobType = $this->createAttribute([
            'translates' => [
                'az' => ['name' => 'İş növü', 'description' => 'İşin növü'],
                'en' => ['name' => 'Job type', 'description' => 'Type of employment']
            ],
            'type' => AttributeTypeEnum::Select,
            'order' => 1
        ]);

        // İş növü seçimləri
        $this->createAttributeOptions($jobType->id, [
            ['translates' => ['az' => ['name' => 'Tam ştat'], 'en' => ['name' => 'Full-time']]],
            ['translates' => ['az' => ['name' => 'Yarım ştat'], 'en' => ['name' => 'Part-time']]],
            ['translates' => ['az' => ['name' => 'Müqavilə'], 'en' => ['name' => 'Contract']]],
            ['translates' => ['az' => ['name' => 'Layihə'], 'en' => ['name' => 'Project-based']]],
            ['translates' => ['az' => ['name' => 'Təcrübə'], 'en' => ['name' => 'Internship']]],
            ['translates' => ['az' => ['name' => 'Freelance'], 'en' => ['name' => 'Freelance']]]
        ]);

        // Təcrübə səviyyəsi
        $experience = $this->createAttribute([
            'translates' => [
                'az' => ['name' => 'Təcrübə', 'description' => 'Tələb olunan təcrübə səviyyəsi'],
                'en' => ['name' => 'Experience', 'description' => 'Required experience level']
            ],
            'type' => AttributeTypeEnum::Select,
            'order' => 2
        ]);

        // Təcrübə seçimləri
        $this->createAttributeOptions($experience->id, [
            ['translates' => ['az' => ['name' => 'Təcrübəsiz'], 'en' => ['name' => 'No experience']]],
            ['translates' => ['az' => ['name' => '1-3 il'], 'en' => ['name' => '1-3 years']]],
            ['translates' => ['az' => ['name' => '3-5 il'], 'en' => ['name' => '3-5 years']]],
            ['translates' => ['az' => ['name' => '5-7 il'], 'en' => ['name' => '5-7 years']]],
            ['translates' => ['az' => ['name' => '7+ il'], 'en' => ['name' => '7+ years']]]
        ]);

        // Təhsil səviyyəsi
        $education = $this->createAttribute([
            'translates' => [
                'az' => ['name' => 'Təhsil', 'description' => 'Tələb olunan təhsil səviyyəsi'],
                'en' => ['name' => 'Education', 'description' => 'Required education level']
            ],
            'type' => AttributeTypeEnum::Select,
            'order' => 3
        ]);

        // Təhsil seçimləri
        $this->createAttributeOptions($education->id, [
            ['translates' => ['az' => ['name' => 'Orta təhsil'], 'en' => ['name' => 'High school']]],
            ['translates' => ['az' => ['name' => 'Peşə təhsili'], 'en' => ['name' => 'Vocational']]],
            ['translates' => ['az' => ['name' => 'Natamam ali'], 'en' => ['name' => 'Incomplete higher']]],
            ['translates' => ['az' => ['name' => 'Bakalavr'], 'en' => ['name' => 'Bachelor']]],
            ['translates' => ['az' => ['name' => 'Magistr'], 'en' => ['name' => 'Master']]],
            ['translates' => ['az' => ['name' => 'Doktorantura'], 'en' => ['name' => 'PhD']]]
        ]);

        // İş qrafiki
        $schedule = $this->createAttribute([
            'translates' => [
                'az' => ['name' => 'İş qrafiki', 'description' => 'İş qrafiki'],
                'en' => ['name' => 'Work schedule', 'description' => 'Working hours']
            ],
            'type' => AttributeTypeEnum::Select,
            'order' => 4
        ]);

        // İş qrafiki seçimləri
        $this->createAttributeOptions($schedule->id, [
            ['translates' => ['az' => ['name' => 'Tam gün'], 'en' => ['name' => 'Full day']]],
            ['translates' => ['az' => ['name' => 'Növbəli'], 'en' => ['name' => 'Shift work']]],
            ['translates' => ['az' => ['name' => 'Çevik qrafik'], 'en' => ['name' => 'Flexible hours']]],
            ['translates' => ['az' => ['name' => 'Uzaqdan iş'], 'en' => ['name' => 'Remote work']]]
        ]);

        // Əmək haqqı diapazonu
        $salary = $this->createAttribute([
            'translates' => [
                'az' => ['name' => 'Əmək haqqı', 'description' => 'Təklif olunan əmək haqqı'],
                'en' => ['name' => 'Salary', 'description' => 'Offered salary']
            ],
            'type' => AttributeTypeEnum::Range,
            'order' => 5,
            'custom_fields' => [
                'min' => 0,
                'max' => 10000,
                'step' => 100,
                'suffix' => 'AZN'
            ]
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
