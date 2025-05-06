<?php

namespace Database\Seeders;

use App\Enums\SeoTypeEnum;
use App\Models\SeoLink;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SeoLinkSeeder extends Seeder
{
    /**
     * Müxtəlif tipli səhifələr üçün SEO strukturu
     */
    protected array $pageTypes = [
        'homepage' => [
            'url' => '/',
            'title' => 'Ana Səhifə | %site_name%',
            'description' => 'Azərbaycanın ən böyük onlayn alış-veriş mərkəzi. Elektronika, geyim, məişət əşyaları və daha çox.',
            'priority' => '1.0',
            'frequency' => 'always'
        ],
        'category' => [
            'url_pattern' => 'category/%s',
            'title_pattern' => '%s Kateqoriyası | %site_name%',
            'description_pattern' => '%s kateqoriyasında ən yaxşı məhsulları onlayn al. Sərfəli qiymətlər və keyfiyyətli xidmət.',
            'priority' => '0.8',
            'frequency' => 'daily'
        ],
        'product' => [
            'url_pattern' => 'product/%s',
            'title_pattern' => '%s | Ən yaxşı qiymətə al',
            'description_pattern' => '%s - %s. İndi sifariş et, sürətli çatdırılma və zəmanətlə al.',
            'priority' => '0.6',
            'frequency' => 'daily'
        ],
        'blog' => [
            'url_pattern' => 'blog/%s',
            'title_pattern' => '%s | Blog',
            'description_pattern' => '%s haqqında ətraflı məlumat əldə edin. Ekspert məsləhətləri və faydalı məlumatlar.',
            'priority' => '0.7',
            'frequency' => 'weekly'
        ],
        'static' => [
            'url_pattern' => '%s',
            'title_pattern' => '%s | %site_name%',
            'description_pattern' => '%s səhifəsi haqqında ətraflı məlumat. Bizim xidmətlər və üstünlüklərimiz.',
            'priority' => '0.5',
            'frequency' => 'monthly'
        ]
    ];

    /**
     * Test datası üçün kateqoriya adları
     */
    protected array $categories = [
        'elektronika' => 'Elektronika və texnika',
        'geyim' => 'Geyim və aksessuarlar',
        'meiset' => 'Məişət əşyaları',
        'usaq' => 'Uşaq malları',
        'idman' => 'İdman və istirahət'
    ];

    /**
     * Test datası üçün məhsul adları
     */
    protected array $products = [
        'iPhone 13 Pro 256GB' => 'Apple şirkətinin ən son flaqman smartfonu, 256GB daxili yaddaş',
        'Samsung 55" QLED TV' => 'Samsung-un premium QLED televizoru, 4K HDR',
        'Nike Air Max 2023' => 'Nike-nin ən rahat qaçış ayaqqabısı',
        'IKEA MALM İş Masası' => 'Skandinav dizaynlı şık və funksional iş masası'
    ];

    /**
     * Test datası üçün blog yazıları
     */
    protected array $blogPosts = [
        'online-alisveris-meslehetleri' => 'Onlayn Alış-Veriş Məsləhətləri',
        'ev-dekorasiya-trendleri' => 'Ev Dekorasiya Trendləri 2024',
        'usaq-terbiyesi' => 'Uşaq Tərbiyəsi Haqqında Bilməli Olduqlarınız',
        'saglamheyat' => 'Sağlam Həyat Tərzinə Doğru 10 Addım'
    ];

    /**
     * Test datası üçün statik səhifələr
     */
    protected array $staticPages = [
        'about' => 'Haqqımızda',
        'contact' => 'Əlaqə',
        'delivery' => 'Çatdırılma',
        'faq' => 'Tez-Tez Verilən Suallar'
    ];

    public function run(): void
    {
        // Köhnə dataları təmizləyirik
        Schema::disableForeignKeyConstraints();
        SeoLink::truncate();
        Schema::enableForeignKeyConstraints();

        // Ana səhifə üçün SEO
        $this->createHomepageSeo();

        // Kateqoriyalar üçün SEO
        foreach ($this->categories as $slug => $name) {
            $this->createCategorySeo($slug, $name);
        }

//        // Məhsullar üçün SEO
//        foreach ($this->products as $name => $description) {
//            $this->createProductSeo($name, $description);
//        }
//
//        // Statik səhifələr üçün SEO
//        foreach ($this->staticPages as $slug => $title) {
//            $this->createStaticPageSeo($slug, $title);
//        }
    }

    /**
     * Ana səhifə üçün SEO yaradır
     */
    protected function createHomepageSeo(): void
    {
        $config = $this->pageTypes['homepage'];

        $this->createSeo([
            'url' => $config['url'],
            'basic_meta' => [
                'title' => strtr($config['title'], ['%site_name%' => config('app.name')]),
                'description' => $config['description'],
                'keywords' => 'onlayn alış-veriş, elektron ticarət, ucuz qiymətlər',
                'robots' => ['index' => true, 'follow' => true],
                'viewport' => 'width=device-width, initial-scale=1.0'
            ],
            'open_graph' => [
                'og:title' => config('app.name'),
                'og:description' => $config['description'],
                'og:type' => 'website',
                'og:image' => 'https://example.com/images/home-og.jpg'
            ],
            'twitter' => [
                'twitter:card' => 'summary_large_image',
                'twitter:title' => config('app.name'),
                'twitter:description' => $config['description'],
                'twitter:image' => 'https://example.com/images/home-twitter.jpg'
            ],
            'technical' => [
                'canonical' => url('/'),
                'language' => 'az',
            ],
            'sitemap_priority' => $config['priority'],
            'sitemap_frequency' => $config['frequency']
        ]);
    }

    /**
     * Kateqoriya üçün SEO yaradır
     */
    protected function createCategorySeo(string $slug, string $name): void
    {
        $config = $this->pageTypes['category'];
        $url = sprintf($config['url_pattern'], $slug);

        // Əvvəlcə kateqoriya adını əlavə edirik
        $title = sprintf('%s Kateqoriyası | %%site_name%%', $name);
        // Sonra site_name-i əvəz edirik
        $title = strtr($title, ['%site_name%' => config('app.name')]);

        $description = sprintf($config['description_pattern'], $name);

        $this->createSeo([
            'url' => $url,
            'seoable_type' => SeoTypeEnum::Category,
            'seoable_id' => rand(1, 10),
            'basic_meta' => [
                'title' => $title,
                'description' => $description,
                'keywords' => "$name, " . Str::slug($name) . ', onlayn alış-veriş',
                'robots' => ['index' => true, 'follow' => true]
            ],
            'open_graph' => [
                'og:title' => $title,
                'og:description' => $description,
                'og:type' => 'website',
                'og:image' => "https://example.com/images/categories/$slug.jpg"
            ],
            'twitter' => [
                'twitter:card' => 'summary_large_image',
                'twitter:title' => $title,
                'twitter:description' => $description
            ],
            'technical' => [
                'canonical' => url($url),
                'language' => 'az',
            ],
            'sitemap_priority' => $config['priority'],
            'sitemap_frequency' => $config['frequency']
        ]);
    }

    /**
     * Məhsul üçün SEO yaradır
     */
    protected function createProductSeo(string $name, string $description): void
    {
        $config = $this->pageTypes['product'];
        $slug = Str::slug($name);
        $url = sprintf($config['url_pattern'], $slug);
        $title = sprintf($config['title_pattern'], $name);
        $seoDescription = sprintf($config['description_pattern'], $name, $description);

        $this->createSeo([
            'url' => $url,
            'seoable_type' => SeoTypeEnum::Listing,
            'seoable_id' => rand(1, 10),
            'basic_meta' => [
                'title' => $title,
                'description' => $seoDescription,
                'keywords' => "$name, " . Str::slug($name) . ', qiymət, satış',
                'robots' => ['index' => true, 'follow' => true]
            ],
            'open_graph' => [
                'og:title' => $title,
                'og:description' => $seoDescription,
                'og:type' => 'product',
                'og:image' => "https://example.com/images/products/$slug.jpg"
            ],
            'twitter' => [
                'twitter:card' => 'summary_large_image',
                'twitter:title' => $title,
                'twitter:description' => mb_substr($seoDescription, 0, 200)
            ],
            'technical' => [
                'canonical' => url($url),
                'language' => 'az',
            ],
            'sitemap_priority' => $config['priority'],
            'sitemap_frequency' => $config['frequency']
        ]);
    }

    /**
     * Statik səhifə üçün SEO yaradır
     */
    protected function createStaticPageSeo(string $slug, string $name): void
    {
        $config = $this->pageTypes['category'];
        $url = sprintf($config['url_pattern'], $slug);

        // Əvvəlcə kateqoriya adını əlavə edirik
        $title = sprintf('%s Kateqoriyası | %%site_name%%', $name);
        // Sonra site_name-i əvəz edirik
        $seoTitle = strtr($title, ['%site_name%' => config('app.name')]);

        $description = sprintf($config['description_pattern'], $name);

        $this->createSeo([
            'url' => $url,
            'seoable_type' => SeoTypeEnum::Page,
            'basic_meta' => [
                'title' => $seoTitle,
                'description' => $description,
                'keywords' => "$title, " . config('app.name'),
                'robots' => ['index' => true, 'follow' => true]
            ],
            'open_graph' => [
                'og:title' => $seoTitle,
                'og:description' => $description,
                'og:type' => 'website',
                'og:image' => "https://example.com/images/pages/$slug.jpg"
            ],
            'twitter' => [
                'twitter:card' => 'summary',
                'twitter:title' => $seoTitle,
                'twitter:description' => $description
            ],
            'technical' => [
                'canonical' => url($url),
                'language' => 'az',
            ],
            'sitemap_priority' => $config['priority'],
            'sitemap_frequency' => $config['frequency']
        ]);
    }

    /**
     * SEO yazısı yaradır və analiz edir
     */
    protected function createSeo(array $data): void
    {
        // Score və tarixçə əlavə edirik
        $data['score'] = $this->calculateInitialScore($data);
        $data['history'] = [
            [
                'action' => 'created',
                'data' => $data,
                'user_id' => 1,
                'user_name' => 'System',
                'timestamp' => now()->toIso8601String()
            ]
        ];

        // SEO yazısını yaradırıq
        SeoLink::create($data);
    }

    /**
     * İlkin SEO score-u hesablayır
     * Bu metod meta tagların keyfiyyətini ölçür və 0-100 arası bir score qaytarır
     *
     * @param array $data SEO məlumatları
     * @return int Hesablanmış SEO score (0-100)
     */
    protected function calculateInitialScore(array $data): int
    {
        $score = 0;
        $maxScore = 100;

        // Title analizi (maksimum 25 xal)
        if (!empty($data['basic_meta']['title'])) {
            $titleLength = mb_strlen($data['basic_meta']['title']);

            // Title uzunluğu - 50-60 simvol arası ideal sayılır
            if ($titleLength >= 50 && $titleLength <= 60) {
                $score += 25; // Mükəmməl uzunluq
            } elseif ($titleLength >= 40 && $titleLength <= 70) {
                $score += 15; // Qəbul edilən uzunluq
            } elseif ($titleLength > 30 && $titleLength < 80) {
                $score += 10; // Minimal tələblərə uyğun
            }

            // Title-da brend adının olması
            if (str_contains($data['basic_meta']['title'], config('app.name'))) {
                $score += 5;
            }
        }

        // Description analizi (maksimum 25 xal)
        if (!empty($data['basic_meta']['description'])) {
            $descLength = mb_strlen($data['basic_meta']['description']);

            // Description uzunluğu - 150-160 simvol arası ideal sayılır
            if ($descLength >= 150 && $descLength <= 160) {
                $score += 25; // Mükəmməl uzunluq
            } elseif ($descLength >= 140 && $descLength <= 170) {
                $score += 15; // Qəbul edilən uzunluq
            } elseif ($descLength > 120 && $descLength < 180) {
                $score += 10; // Minimal tələblərə uyğun
            }

            // Description-da açar sözlərin olması
            if (!empty($data['basic_meta']['keywords'])) {
                $keywords = explode(',', $data['basic_meta']['keywords']);
                foreach ($keywords as $keyword) {
                    if (str_contains(strtolower($data['basic_meta']['description']), strtolower(trim($keyword)))) {
                        $score += 2; // Hər açar söz üçün əlavə xal
                    }
                }
            }
        }

        // Sosial Media Optimizasiyası (maksimum 20 xal)
        if (!empty($data['open_graph'])) {
            // OpenGraph əsas tagları
            $ogTags = ['og:title', 'og:description', 'og:image', 'og:type'];
            foreach ($ogTags as $tag) {
                if (!empty($data['open_graph'][$tag])) {
                    $score += 3;
                }
            }
        }

        if (!empty($data['twitter'])) {
            // Twitter Card əsas tagları
            $twitterTags = ['twitter:card', 'twitter:title', 'twitter:description'];
            foreach ($twitterTags as $tag) {
                if (!empty($data['twitter'][$tag])) {
                    $score += 2;
                }
            }
        }

        // Texniki Optimizasiya (maksimum 20 xal)
        if (!empty($data['technical'])) {
            // Canonical URL
            if (!empty($data['technical']['canonical'])) {
                $score += 5;
            }

            // Dil tag-ı
            if (!empty($data['technical']['language'])) {
                $score += 5;
            }

            // Content-Type
            if (!empty($data['technical']['content-type'])) {
                $score += 5;
            }
        }

        // Robots direktivi (maksimum 5 xal)
        if (!empty($data['basic_meta']['robots'])) {
            $robots = $data['basic_meta']['robots'];
            if (isset($robots['index']) && isset($robots['follow'])) {
                $score += 5;
            }
        }

        // Keywords analizi (maksimum 5 xal)
        if (!empty($data['basic_meta']['keywords'])) {
            $keywords = explode(',', $data['basic_meta']['keywords']);
            $keywordCount = count($keywords);

            // 5-7 açar söz optimal sayılır
            if ($keywordCount >= 5 && $keywordCount <= 7) {
                $score += 5;
            } elseif ($keywordCount >= 3 && $keywordCount <= 10) {
                $score += 3;
            }
        }

        // Sitemap konfiqurasiyası (maksimum 5 xal)
        if (isset($data['sitemap_priority']) && isset($data['sitemap_frequency'])) {
            $score += 5;
        }

        // URL strukturu (maksimum 5 xal)
        if (!empty($data['url'])) {
            $url = $data['url'];

            // URL təmizliyi və SEO-dostluğu
            if (strlen($url) <= 100 && !str_contains($url, '?') && !str_contains($url, '#')) {
                $score += 5;
            }
        }

        // Səhifə tipi və kontekst (maksimum 5 xal)
        if (!empty($data['seoable_type'])) {
            switch ($data['seoable_type']) {
                case 'product':
                    // Məhsul səhifələri üçün xüsusi yoxlamalar
                    if (!empty($data['open_graph']['og:type']) && $data['open_graph']['og:type'] === 'product') {
                        $score += 5;
                    }
                    break;
                case 'category':
                    // Kateqoriya səhifələri üçün yoxlamalar
                    if (str_contains(strtolower($data['basic_meta']['title'] ?? ''), 'kateqoriya')) {
                        $score += 5;
                    }
                    break;
                case 'blog':
                    // Blog yazıları üçün yoxlamalar
                    if (!empty($data['open_graph']['og:type']) && $data['open_graph']['og:type'] === 'article') {
                        $score += 5;
                    }
                    break;
            }
        }

        // Score-u maksimum 100-ə məhdudlaşdırırıq
        return min($score, $maxScore);
    }

    /**
     * Analiz nəticələrini generasiya edir
     */
    protected function generateAnalysis(array $data): array
    {
        $analysis = [];

        // Title analizi
        if (!empty($data['basic_meta']['title'])) {
            $titleLength = mb_strlen($data['basic_meta']['title']);
            if ($titleLength >= 50 && $titleLength <= 60) {
                $analysis[] = [
                    'type' => 'success',
                    'message' => 'Title uzunluğu idealdir'
                ];
            } else {
                $analysis[] = [
                    'type' => 'warning',
                    'message' => 'Title uzunluğu optimal deyil'
                ];
            }
        }

        // Description analizi
        if (!empty($data['basic_meta']['description'])) {
            $descLength = mb_strlen($data['basic_meta']['description']);
            if ($descLength >= 150 && $descLength <= 160) {
                $analysis[] = [
                    'type' => 'success',
                    'message' => 'Description uzunluğu idealdir'
                ];
            } else {
                $analysis[] = [
                    'type' => 'warning',
                    'message' => 'Description uzunluğu optimal deyil'
                ];
            }
        }

        // Meta tagların tamlığı
        foreach (['open_graph', 'twitter'] as $group) {
            if (empty($data[$group])) {
                $analysis[] = [
                    'type' => 'error',
                    'message' => ucfirst($group) . ' tagları əskikdir'
                ];
            }
        }

        // Canonical URL yoxlaması
        if (empty($data['technical']['canonical'])) {
            $analysis[] = [
                'type' => 'warning',
                'message' => 'Canonical URL təyin edilməyib'
            ];
        }

        return $analysis;
    }

    /**
     * Test məlumatları əlavə edir
     */
    protected function addTestData(): void
    {
        // Axtarış səhifəsi
        $this->createSeo([
            'url' => 'search',
            'seoable_type' => 'page',
            'basic_meta' => [
                'title' => 'Axtarış Nəticələri',
                'robots' => ['noindex' => true, 'follow' => true]
            ],
            'is_sitemap' => false
        ]);

        // Xəta səhifəsi (404)
        $this->createSeo([
            'url' => '404',
            'seoable_type' => 'error',
            'basic_meta' => [
                'title' => 'Səhifə Tapılmadı | 404',
                'robots' => ['noindex' => true, 'nofollow' => true]
            ],
            'is_sitemap' => false
        ]);

        // Ödəniş səhifəsi
        $this->createSeo([
            'url' => 'checkout',
            'seoable_type' => 'page',
            'basic_meta' => [
                'title' => 'Ödəniş | ' . config('app.name'),
                'robots' => ['noindex' => true, 'nofollow' => true]
            ],
            'is_sitemap' => false
        ]);
    }
}
