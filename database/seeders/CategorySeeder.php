<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\Category;
use App\Models\CategoryAttribute;
use App\Models\SeoLink;
use App\Services\Module\AttributeService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        // Əvvəlcə cədvəli təmizləyirik
        Schema::disableForeignKeyConstraints();
        CategoryAttribute::query()->truncate();
        Category::truncate();
        SeoLink::query()->where('seoable_type', 'App\Models\Category')->delete();
        $this->createCategoryStructure();
        Schema::enableForeignKeyConstraints();
    }

    private function createCategoryStructure(): void
    {
        // 1. Uşaq aləmi
        $childWorld = $this->createMainCategory('Uşaq aləmi', 'Children\'s World', 1, 'fas fa-baby');
        $this->createChildCategories($childWorld->id, [
            ['slug' => 'avtomobil-oturacaqlari', 'name' => 'Avtomobil oturacaqları'],
            ['slug' => 'oyuncaqlar', 'name' => 'Oyuncaqlar'],
            ['slug' => 'usaq-arabalari', 'name' => 'Uşaq arabaları'],
            ['slug' => 'usaq-avtomobilleri', 'name' => 'Uşaq avtomobilləri'],
            ['slug' => 'usaq-carpayilari-ve-beshikler', 'name' => 'Çarpayılar və beşiklər'],
            ['slug' => 'usaq-dasiyicilari', 'name' => 'Uşaq daşıyıcıları'],
            ['slug' => 'usaq-geyimleri', 'name' => 'Uşaq geyimi'],
            ['slug' => 'mebel', 'name' => 'Uşaq mebeli'],
            ['slug' => 'usaq-yemekleri', 'name' => 'Uşaq qidası və bəslənməsi'],
            ['slug' => 'usaq-suruskenleri-ve-oyun-meydancalari', 'name' => 'Sürüşkənlər və meydançalar'],
            ['slug' => 'manejler', 'name' => 'Manejlər'],
            ['slug' => 'mekteb-levazimatlari', 'name' => 'Məktəblilər üçün'],
            ['slug' => 'yurutecler', 'name' => 'Yürütəclər'],
            ['slug' => 'usaq-gigiyenasi', 'name' => 'Hamam və gigiyena'],
            ['slug' => 'usaq-tekstili', 'name' => 'Uşaq tekstili'],
            ['slug' => 'qidalanma-ucun-usaq-oturacaqlar', 'name' => 'Qidalanma oturacaqları'],
            ['slug' => 'her-sey', 'name' => 'Digər'],
        ]);

        // 2. Şəxsi əşyalar
        $personalItems = $this->createMainCategory('Şəxsi əşyalar', 'Personal Items', 2, 'fas fa-user');
        $this->createChildCategories($personalItems->id, [
            ['slug' => 'geyim-ayaqqabilar', 'name' => 'Geyim və ayaqqabılar'],
            ['slug' => 'saatlar-zinet-esyalari', 'name' => 'Saat və zinət əşyaları'],
            ['slug' => 'aksesuarlar', 'name' => 'Aksesuarlar'],
            ['slug' => 'gozellik-saglamliq', 'name' => 'Sağlamlıq və gözəllik'],
            ['slug' => 'itmis-esyalar', 'name' => 'İtmiş əşyalar'],
            ['slug' => 'elektron-siqaretler', 'name' => 'Elektron siqaretlər və tütün qızdırıcıları'],
        ]);

        // 3. Ev və bağ üçün
        $homeAndGarden = $this->createMainCategory('Ev və bağ üçün', 'Home and Garden', 3, 'fas fa-home');
        $this->createChildCategories($homeAndGarden->id, [
            ['slug' => 'temir-tikinti', 'name' => 'Təmir və tikinti'],
            ['slug' => 'mebel', 'name' => 'Mebellər'],
            ['slug' => 'meiset-texnikasi', 'name' => 'Məişət texnikası'],
            ['slug' => 'erzaq', 'name' => 'Ərzaq'],
            ['slug' => 'qab-qacaq', 'name' => 'Qab-qacaq və mətbəx ləvazimatları'],
            ['slug' => 'bitkiler', 'name' => 'Bitkilər'],
            ['slug' => 'xalcalar-aksesuarlar', 'name' => 'Xalçalar və aksesuarlar'],
            ['slug' => 'ev-tekstili', 'name' => 'Ev tekstili'],
            ['slug' => 'ev-bag-ucun-ishiqlandirma', 'name' => 'Ev və bağ üçün işiqlandırma'],
            ['slug' => 'dekor-interyer', 'name' => 'Dekor və interyer'],
            ['slug' => 'bag-bostan', 'name' => 'Bağ və bostan'],
            ['slug' => 'ev-teserrufati-mallari', 'name' => 'Ev təsərrüfatı malları'],
        ]);

        // 4. Elektronika
        $electronics = $this->createMainCategory('Elektronika', 'Electronics', 4, 'fas fa-laptop');
        $this->createChildCategories($electronics->id, [
            ['slug' => 'audio-video', 'name' => 'Audio və video'],
            ['slug' => 'komputer-aksesuarlari', 'name' => 'Kompüter aksesuarları'],
            ['slug' => 'oyunlar-ve-programlar', 'name' => 'Oyunlar, pultlar və proqramlar'],
            ['slug' => 'komputerler', 'name' => 'Masaüstü kompüterlər'],
            ['slug' => 'komputer-avadanliqi', 'name' => 'Komponentlər və monitorlar'],
            ['slug' => 'plansetler', 'name' => 'Planşet və elektron kitablar'],
            ['slug' => 'noutbuklar', 'name' => 'Noutbuklar və netbuklar'],
            ['slug' => 'ofis-avadanliqi', 'name' => 'Ofis avadanlığı və istehlak materialları'],
            ['slug' => 'telefonlar', 'name' => 'Telefonlar'],
            ['slug' => 'nomreler-ve-sim-kartlar', 'name' => 'Nömrələr və SIM-kartlar'],
            ['slug' => 'fotoaparatlar-ve-linzalar', 'name' => 'Fototexnika'],
            ['slug' => 'smart-saat-ve-qolbaqlar', 'name' => 'Smart saat və qolbaqlar'],
            ['slug' => 'televizor-ve-aksesuarlar', 'name' => 'Televizorlar və aksesuarlar'],
            ['slug' => 'sebeke-avadanligi', 'name' => 'Şəbəkə və server avadanlığı'],
        ]);

        // 5. Hobbi və asudə
        $hobby = $this->createMainCategory('Hobbi və asudə', 'Hobbies and Leisure', 5, 'fas fa-gamepad');
        $this->createChildCategories($hobby->id, [
            ['slug' => 'turlar-ve-biletler', 'name' => 'Biletlər və səyahət'],
            ['slug' => 'velosipedler', 'name' => 'Velosipedlər'],
            ['slug' => 'kolleksiyalar', 'name' => 'Kolleksiyalar'],
            ['slug' => 'musiqi-aletleri', 'name' => 'Musiqi alətləri'],
            ['slug' => 'idman-ve-asude', 'name' => 'İdman və asudə'],
            ['slug' => 'kitab-ve-jurnallar', 'name' => 'Kitab və jurnallar'],
            ['slug' => 'kempinq-ovculuq-baliqciliq', 'name' => 'Kempinq, ovçuluq və balıqçılıq'],
            ['slug' => 'tanisliq', 'name' => 'Tanışlıq'],
        ]);

        // 6. Nəqliyyat
        $transport = $this->createMainCategory('Nəqliyyat', 'Transport', 6, 'fas fa-car');
        $this->createChildCategories($transport->id, [
            ['slug' => 'avtomobiller', 'name' => 'Avtomobillər'],
            ['slug' => 'ehtiyyat-hisseleri-ve-aksesuarlar', 'name' => 'Ehtiyat hissələri və aksesuarlar'],
            ['slug' => 'motosikletler-mopedler', 'name' => 'Motosikletlər və mopedlər'],
            ['slug' => 'su-neqliyyati', 'name' => 'Su nəqliyyatı'],
            ['slug' => 'tikinti-texnikasi', 'name' => 'Tikinti texnikası'],
            ['slug' => 'aqrotexnika', 'name' => 'Aqrotexnika'],
            ['slug' => 'avtobuslar', 'name' => 'Avtobuslar'],
            ['slug' => 'yuk-masinlari-ve-qosqular', 'name' => 'Yük maşınları və qoşqular'],
            ['slug' => 'qeydiyyat-nisanlari', 'name' => 'Qeydiyyat nişanları'],
        ]);

        // 7. Daşınmaz əmlak
        $realEstate = $this->createMainCategory('Daşınmaz əmlak', 'Real Estate', 7, 'fas fa-building');
        $this->createChildCategories($realEstate->id, [
            ['slug' => 'menziller', 'name' => 'Mənzillər'],
            ['slug' => 'heyet-evleri', 'name' => 'Həyət evləri, bağ evləri'],
            ['slug' => 'torpaq-sahesi', 'name' => 'Torpaq'],
            ['slug' => 'qarajlar', 'name' => 'Qarajlar'],
            ['slug' => 'xaricde-emlak', 'name' => 'Xaricdə əmlak'],
            ['slug' => 'obyektler-ve-ofisler', 'name' => 'Obyektlər və ofislər'],
        ]);

        // 8. İş elanları
        $jobs = $this->createMainCategory('İş elanları', 'Job Listings', 8, 'fas fa-briefcase');
        $this->createChildCategories($jobs->id, [
            ['slug' => 'vakansiyalar', 'name' => 'Vakansiyalar'],
            ['slug' => 'is-axtariram', 'name' => 'İş axtarıram'],
        ]);

        // 9. Heyvanlar
        $animals = $this->createMainCategory('Heyvanlar', 'Animals', 9, 'fas fa-paw');
        $this->createChildCategories($animals->id, [
            ['slug' => 'itler', 'name' => 'İtlər'],
            ['slug' => 'pisikler', 'name' => 'Pişiklər'],
            ['slug' => 'quslar', 'name' => 'Quşlar'],
            ['slug' => 'baliqlar-akvariumlar', 'name' => 'Akvariumlar və balıqlar'],
            ['slug' => 'kt-heyvanlari', 'name' => 'K/t heyvanları'],
            ['slug' => 'heyvanlar-ucun-mehsullar', 'name' => 'Heyvanlar üçün məhsullar'],
            ['slug' => 'dovsanlar', 'name' => 'Dovşanlar'],
            ['slug' => 'diger-heyvanlar', 'name' => 'Digər heyvanlar'],
            ['slug' => 'atlar', 'name' => 'Atlar'],
            ['slug' => 'gemiriciler', 'name' => 'Gəmiricilər'],
        ]);

        // 10. Xidmətlər və biznes
        $services = $this->createMainCategory('Xidmətlər və biznes', 'Services and Business', 10, 'fas fa-handshake');
        $this->createChildCategories($services->id, [
            ['slug' => 'avadanliqin-icaresi', 'name' => 'Avadanlığın icarəsi'],
            ['slug' => 'avadanliqin-qurasdirilmasi', 'name' => 'Avadanlıqların quraşdırılması'],
            ['slug' => 'biznes-avadaliqi', 'name' => 'Biznes üçün avadanlıq'],
            ['slug' => 'avtoservis-ve-diaqnostika', 'name' => 'Avtoservis və diaqnostika'],
            ['slug' => 'dayeler-baxicilar', 'name' => 'Dayələr, baxıcılar'],
            ['slug' => 'foto-ve-video-cekilis', 'name' => 'Foto və video çəkiliş xidmətləri'],
            ['slug' => 'gozellik-xidmetleri', 'name' => 'Gözəllik, sağlamlıq'],
            ['slug' => 'huquq-xidmetleri', 'name' => 'Hüquq xidmətləri'],
            ['slug' => 'komputer-xidmetleri', 'name' => 'IT, internet, telekom'],
            ['slug' => 'logistika', 'name' => 'Logistika'],
            ['slug' => 'sifarisle-mebel', 'name' => 'Mebel yığılması və təmiri'],
            ['slug' => 'tedbirlerin-teskilati', 'name' => 'Musiqi, əyləncə və tədbirlər'],
            ['slug' => 'muhasibat-xidmetleri', 'name' => 'Mühasibat xidmətləri'],
            ['slug' => 'neqliyyat-icaresi', 'name' => 'Nəqliyyat vasitələrinin icarəsi'],
            ['slug' => 'keyterinq-xidmetleri', 'name' => 'Qidalanma, keyterinq'],
            ['slug' => 'reklam-xidmetleri', 'name' => 'Reklam, dizayn və poliqrafiya'],
            ['slug' => 'sigorta-xidmetleri', 'name' => 'Sığorta xidmətləri'],
            ['slug' => 'tehlukesizlik-sistemleri', 'name' => 'Təhlükəsizlik sistemlərinin qurulması'],
            ['slug' => 'telim-hazirliq-kurslari', 'name' => 'Təlim, hazırlıq kursları'],
            ['slug' => 'temir-tikinti', 'name' => 'Təmir və tikinti'],
            ['slug' => 'temizlik-xidmeti', 'name' => 'Təmizlik'],
            ['slug' => 'tercume-xidmetleri', 'name' => 'Tərcümə'],
            ['slug' => 'texnika-temiri', 'name' => 'Texnika təmiri'],
            ['slug' => 'tibbi-xidmetler', 'name' => 'Tibbi xidmətlər'],
            ['slug' => 'diger-xidmetler', 'name' => 'Digər'],
        ]);
    }

    /**
     * Ana kateqoriya yaradır
     */
    private function createMainCategory(string $nameAz, string $nameEn, int $order, string $icon): Category
    {
        $category =  $this->createCategory([
            'translates' => [
                'az' => ['name' => $nameAz, 'description' => $nameAz . ' kateqoriyası'],
                'en' => ['name' => $nameEn, 'description' => $nameEn . ' category']
            ],
            'meta_tags' => [
                'title' => $nameAz,
                'description' => $nameAz . ' - elanlar və satış',
                'keywords' => $nameAz . ', elan, satış'
            ],
            'icon' => $icon,
            'order' => $order,
            'is_default' => false,
            'is_active' => true
        ]);

        $attributes = Attribute::query()->active()->inRandomOrder()->limit(rand(5, 10))->get()->pluck('id')->toArray();

        foreach ($attributes as $attribute) {
            $category->attributes()->create([
                'attribute_id' => $attribute,
                'is_required'  => rand(10, 50) % 3 === 0,
                'is_visible'  => rand(10, 50) % 10 === 0,
            ]);
        }

        return $category;
    }

    /**
     * Alt kateqoriyalar yaradır
     */
    private function createChildCategories(int $parentId, array $categories): void
    {
        foreach ($categories as $index => $category) {
            $this->createCategory([
                'translates' => [
                    'az' => [
                        'name' => $category['name'],
                        'description' => $category['name']
                    ],
                    'en' => [
                        'name' => ucfirst(str_replace('-', ' ', $category['slug'])),
                        'description' => ucfirst(str_replace('-', ' ', $category['slug']))
                    ]
                ],
                'meta_tags' => [
                    'title' => $category['name'],
                    'description' => $category['name'] . ' - elanlar',
                    'keywords' => $category['name'] . ', elan'
                ],
                'parent_id' => $parentId,
                'order' => $index + 1,
                'slug' => $category['slug'],
                'is_default' => false,
                'is_active' => true
            ]);
        }
    }

    /**
     * Kateqoriya yaradır
     */
    private function createCategory(array $data): Category
    {
        return Category::create(array_merge([
            'slug' => $data['slug'] ?? Str::slug($data['translates']['az']['name']),
            'parent_id' => $data['parent_id'] ?? 0,
            'is_active' => true,
            'is_default' => false,
        ], $data));
    }
}
