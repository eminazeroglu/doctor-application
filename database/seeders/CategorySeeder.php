<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\Category;
use App\Models\CategoryAttribute;
use App\Models\SeoLink;
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
        // 1. Terapevtik sahələr
        $therapeutic = $this->createMainCategory('Terapevtik sahələr', 'Therapeutic Fields', 1, 'fas fa-heartbeat');
        $this->createChildCategories($therapeutic->id, [
            ['slug' => 'terapevt', 'name' => 'Terapevt'],
            ['slug' => 'kardioloq', 'name' => 'Kardioloq'],
            ['slug' => 'allerqoloq', 'name' => 'Allerqoloq'],
            ['slug' => 'endokrinoloq', 'name' => 'Endokrinoloq'],
            ['slug' => 'qastroenteroloq', 'name' => 'Qastroenteroloq'],
            ['slug' => 'hematoloq', 'name' => 'Hematoloq'],
            ['slug' => 'infeksionist', 'name' => 'İnfeksionist'],
            ['slug' => 'nefroloq', 'name' => 'Nefroloq'],
            ['slug' => 'pulmonoloq', 'name' => 'Pulmonoloq'],
            ['slug' => 'revmatoloq', 'name' => 'Revmatoloq'],
            ['slug' => 'toksikoloq', 'name' => 'Toksikoloq'],
            ['slug' => 'immunoloq', 'name' => 'İmmunoloq'],
            ['slug' => 'dermatoloq', 'name' => 'Dermatoloq'],
        ]);

        // 2. Cərrahiyyə sahələri
        $surgery = $this->createMainCategory('Cərrahiyyə sahələri', 'Surgical Fields', 2, 'fas fa-user-md');
        $this->createChildCategories($surgery->id, [
            ['slug' => 'umumi-cerrah', 'name' => 'Ümumi cərrah'],
            ['slug' => 'kardiocerrah', 'name' => 'Kardiocərrah'],
            ['slug' => 'neyrocerrah', 'name' => 'Neyrocərrah'],
            ['slug' => 'plastik-cerrah', 'name' => 'Plastik cərrah'],
            ['slug' => 'uroloji-cerrah', 'name' => 'Uroloji cərrah'],
            ['slug' => 'ortoped-travmatoloq', 'name' => 'Ortoped-travmatoloq'],
            ['slug' => 'laparoskopikcerrah', 'name' => 'Laparoskopik cərrah'],
            ['slug' => 'onkoloq-cerrah', 'name' => 'Onkoloq-cərrah'],
            ['slug' => 'damar-cerrahi', 'name' => 'Damar cərrahı'],
            ['slug' => 'transplantolog', 'name' => 'Transplantolog'],
            ['slug' => 'estetik-cerrah', 'name' => 'Estetik cərrah'],
        ]);

        // 3. Diaqnostika və laboratoriya
        $diagnostics = $this->createMainCategory('Diaqnostika və laboratoriya', 'Diagnostics and Laboratory', 3, 'fas fa-microscope');
        $this->createChildCategories($diagnostics->id, [
            ['slug' => 'rentgenoloq', 'name' => 'Rentgenoloq'],
            ['slug' => 'ultrasonoqrafiya', 'name' => 'Ultrasonoqrafiya mütəxəssisi'],
            ['slug' => 'kt-mri-mutexessisi', 'name' => 'KT və MRT mütəxəssisi'],
            ['slug' => 'mammoloq', 'name' => 'Mammoloq'],
            ['slug' => 'patoloq', 'name' => 'Patoloq'],
            ['slug' => 'radioloq', 'name' => 'Radioloq'],
            ['slug' => 'klinik-laborant', 'name' => 'Klinik laborant'],
            ['slug' => 'endoskopist', 'name' => 'Endoskopist'],
            ['slug' => 'funksional-diaqnostika', 'name' => 'Funksional diaqnostika mütəxəssisi'],
            ['slug' => 'genetik', 'name' => 'Genetik'],
        ]);

        // 4. Pediatriya
        $pediatrics = $this->createMainCategory('Pediatriya', 'Pediatrics', 4, 'fas fa-baby');
        $this->createChildCategories($pediatrics->id, [
            ['slug' => 'pediatr', 'name' => 'Pediatr'],
            ['slug' => 'usaq-kardioloqu', 'name' => 'Uşaq kardioloqu'],
            ['slug' => 'usaq-nevroloqu', 'name' => 'Uşaq nevroloqu'],
            ['slug' => 'usaq-cerrah', 'name' => 'Uşaq cərrahı'],
            ['slug' => 'usaq-ortopedi', 'name' => 'Uşaq ortopedi'],
            ['slug' => 'usaq-oftalmoloqu', 'name' => 'Uşaq oftalmoloqu'],
            ['slug' => 'usaq-otolarinqoloqu', 'name' => 'Uşaq otolarinqoloqu (LOR)'],
            ['slug' => 'usaq-stomatoloqu', 'name' => 'Uşaq stomatoloqu'],
            ['slug' => 'usaq-endokrinoloqu', 'name' => 'Uşaq endokrinoloqu'],
            ['slug' => 'neonatoloq', 'name' => 'Neonatoloq'],
            ['slug' => 'usaq-allergologi', 'name' => 'Uşaq allerqoloqu'],
            ['slug' => 'usaq-psixoloqu', 'name' => 'Uşaq psixoloqu'],
            ['slug' => 'usaq-qastroenteroloqu', 'name' => 'Uşaq qastroenteroloqu'],
        ]);

        // 5. Qadın sağlamlığı
        $womensHealth = $this->createMainCategory('Qadın sağlamlığı', 'Women\'s Health', 5, 'fas fa-female');
        $this->createChildCategories($womensHealth->id, [
            ['slug' => 'ginekoloq', 'name' => 'Ginekoloq'],
            ['slug' => 'mama', 'name' => 'Mama'],
            ['slug' => 'reproduktoloq', 'name' => 'Reproduktoloq'],
            ['slug' => 'mamaliginekoloq', 'name' => 'Mama-ginekoloq'],
            ['slug' => 'ginekoloq-endokrinoloq', 'name' => 'Ginekoloq-endokrinoloq'],
            ['slug' => 'onkoqinekoloq', 'name' => 'Onkoginekoloq'],
            ['slug' => 'estetik-ginekoloq', 'name' => 'Estetik ginekoloq'],
            ['slug' => 'akuşer', 'name' => 'Akuşer'],
        ]);

        // 6. Stomatoloji xidmətlər
        $dental = $this->createMainCategory('Stomatoloji xidmətlər', 'Dental Services', 6, 'fas fa-tooth');
        $this->createChildCategories($dental->id, [
            ['slug' => 'terapevt-stomatoloq', 'name' => 'Terapevt stomatoloq'],
            ['slug' => 'cerrah-stomatoloq', 'name' => 'Cərrah stomatoloq'],
            ['slug' => 'ortodont', 'name' => 'Ortodont'],
            ['slug' => 'ortoped-stomatoloq', 'name' => 'Ortoped stomatoloq'],
            ['slug' => 'usaq-stomatoloqu', 'name' => 'Uşaq stomatoloqu'],
            ['slug' => 'endodontist', 'name' => 'Endodontist'],
            ['slug' => 'parodontoloq', 'name' => 'Parodontoloq'],
            ['slug' => 'implantolog', 'name' => 'İmplantolog'],
            ['slug' => 'estetik-stomatoloq', 'name' => 'Estetik stomatoloq'],
        ]);

        // 7. Göz sağlamlığı
        $eyeHealth = $this->createMainCategory('Göz sağlamlığı', 'Eye Health', 7, 'fas fa-eye');
        $this->createChildCategories($eyeHealth->id, [
            ['slug' => 'oftalmoloq', 'name' => 'Oftalmoloq'],
            ['slug' => 'goz-cerrahi', 'name' => 'Göz cərrahı'],
            ['slug' => 'qlakomatoloq', 'name' => 'Qlakomatoloq'],
            ['slug' => 'retina-mutexessisi', 'name' => 'Retina mütəxəssisi'],
            ['slug' => 'katarakt-cerrahi', 'name' => 'Katarakt cərrahı'],
            ['slug' => 'optometrist', 'name' => 'Optometrist'],
            ['slug' => 'usaq-oftalmoloqu', 'name' => 'Uşaq oftalmoloqu'],
            ['slug' => 'refraksiya-mutexessisi', 'name' => 'Refraksiya mütəxəssisi'],
        ]);

        // 8. Əsəb və ruhi sağlamlıq
        $mentalHealth = $this->createMainCategory('Əsəb və ruhi sağlamlıq', 'Mental Health', 8, 'fas fa-brain');
        $this->createChildCategories($mentalHealth->id, [
            ['slug' => 'nevroloq', 'name' => 'Nevroloq'],
            ['slug' => 'psixiatr', 'name' => 'Psixiatr'],
            ['slug' => 'psixoloq', 'name' => 'Psixoloq'],
            ['slug' => 'psixoterapevt', 'name' => 'Psixoterapevt'],
            ['slug' => 'narkoloq', 'name' => 'Narkoloq'],
            ['slug' => 'neyropsixoloq', 'name' => 'Neyropsixoloq'],
            ['slug' => 'usaq-psixiatr', 'name' => 'Uşaq psixiatrı'],
            ['slug' => 'usaq-psixoloq', 'name' => 'Uşaq psixoloqu'],
            ['slug' => 'seksolog', 'name' => 'Seksoloq'],
            ['slug' => 'aile-psixoloqu', 'name' => 'Ailə psixoloqu'],
            ['slug' => 'yuxu-pozgunu-mutexessisi', 'name' => 'Yuxu pozğunluğu mütəxəssisi'],
        ]);

        // 9. Reabilitasiya və fizioterapiya
        $rehabilitation = $this->createMainCategory('Reabilitasiya və fizioterapiya', 'Rehabilitation and Physiotherapy', 9, 'fas fa-walking');
        $this->createChildCategories($rehabilitation->id, [
            ['slug' => 'fizioterapevt', 'name' => 'Fizioterapevt'],
            ['slug' => 'reabilitoloq', 'name' => 'Reabilitoloq'],
            ['slug' => 'manual-terapevt', 'name' => 'Manual terapevt'],
            ['slug' => 'kineziterapevt', 'name' => 'Kineziterapevt'],
            ['slug' => 'idman-hekimi', 'name' => 'İdman həkimi'],
            ['slug' => 'osteopat', 'name' => 'Osteopat'],
            ['slug' => 'massaj-mutexessisi', 'name' => 'Massaj mütəxəssisi'],
            ['slug' => 'loqoped', 'name' => 'Loqoped'],
            ['slug' => 'erqoterapevt', 'name' => 'Erqoterapevt'],
            ['slug' => 'fizioterapiya-texniki', 'name' => 'Fizioterapiya texniki'],
        ]);

        // 10. Digər tibbi sahələr
        $otherMedical = $this->createMainCategory('Digər tibbi sahələr', 'Other Medical Fields', 10, 'fas fa-stethoscope');
        $this->createChildCategories($otherMedical->id, [
            ['slug' => 'otolarinqoloq', 'name' => 'Otolarinqoloq (LOR)'],
            ['slug' => 'dietoloq', 'name' => 'Dietoloq'],
            ['slug' => 'homeopat', 'name' => 'Homeopat'],
            ['slug' => 'onkoloq', 'name' => 'Onkoloq'],
            ['slug' => 'uroloq', 'name' => 'Uroloq'],
            ['slug' => 'androloq', 'name' => 'Androloq'],
            ['slug' => 'geriator', 'name' => 'Geriator'],
            ['slug' => 'kosmetolog', 'name' => 'Kosmetoloq'],
            ['slug' => 'refleksoterapevt', 'name' => 'Refleksoterapevt'],
            ['slug' => 'tibbi-genetik', 'name' => 'Tibbi genetik'],
            ['slug' => 'hirudoterapevt', 'name' => 'Hirudoterapevt'],
            ['slug' => 'ftiziatr', 'name' => 'Ftiziatr'],
            ['slug' => 'proktolog', 'name' => 'Proktoloq'],
        ]);
    }

    /**
     * Ana kateqoriya yaradır
     */
    private function createMainCategory(string $nameAz, string $nameEn, int $order, string $icon): Category
    {
        $category = $this->createCategory([
            'translates' => [
                'az' => ['name' => $nameAz, 'description' => $nameAz . ' sahəsində ixtisaslaşmış həkimlər'],
                'en' => ['name' => $nameEn, 'description' => 'Doctors specializing in ' . $nameEn]
            ],
            'meta_tags' => [
                'title' => $nameAz . ' - Həkimlər',
                'description' => $nameAz . ' sahəsində ixtisaslaşmış həkimlər',
                'keywords' => $nameAz . ', həkimlər, tibbi xidmətlər, mütəxəssislər'
            ],
            'icon' => $icon,
            'order' => $order,
            'is_default' => false,
            'is_active' => true
        ]);

        // Atributları əlavə edirik (ixtisas, təcrübə, məsləhət qiyməti və s.)
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
                        'description' => $category['name'] . ' ixtisası üzrə həkimlər'
                    ],
                    'en' => [
                        'name' => ucfirst(str_replace('-', ' ', $category['slug'])),
                        'description' => 'Doctors specializing in ' . ucfirst(str_replace('-', ' ', $category['slug']))
                    ]
                ],
                'meta_tags' => [
                    'title' => $category['name'] . ' - Həkimlər',
                    'description' => $category['name'] . ' ixtisası üzrə həkimlər',
                    'keywords' => $category['name'] . ', həkim, mütəxəssis, tibbi xidmət'
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
