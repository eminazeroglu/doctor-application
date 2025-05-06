<?php

namespace App\Services\Module;

use App\Models\SeoLink;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class SeoLinkToolsService
{
    protected SeoLinkService $seoService;

    /**
     * Cache-də saxlama müddəti (6 saat)
     */
    protected const CACHE_TTL = 21600;

    public function __construct(SeoLinkService $seoService)
    {
        $this->seoService = $seoService;
    }

    /**
     * Sitemap.xml faylını generasiya edir
     *
     * Bu metod bütün aktiv SEO yazılarını toplayır və onlardan
     * sitemap.xml faylı yaradır. Performans üçün nəticə cache-də saxlanılır.
     */
    public function generateSitemap(): string
    {
        return Cache::remember('sitemap_content', self::CACHE_TTL, function () {
            $activePages = $this->seoService->getSitemapData();

            $xml = '<?xml version="1.0" encoding="UTF-8"?>';
            $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

            foreach ($activePages as $page) {
                $xml .= $this->generateSitemapUrl($page);
            }

            $xml .= '</urlset>';

            // Sitemap-i fiziki olaraq saxlayırıq
            Storage::disk('public')->put('sitemap.xml', $xml);

            return $xml;
        });
    }

    /**
     * Sitemap üçün URL node-u yaradır
     *
     * Bu metod hər bir SEO yazısı üçün <url> elementini yaradır,
     * içində lazımi məlumatları (lastmod, changefreq, priority) yerləşdirir.
     */
    protected function generateSitemapUrl(SeoLink $page): string
    {
        $url = url($page->url);
        $lastmod = $page->updated_at->toAtomString();
        $changefreq = $page->sitemap_frequency;
        $priority = $page->sitemap_priority;

        return "
            <url>
                <loc>{$url}</loc>
                <lastmod>{$lastmod}</lastmod>
                <changefreq>{$changefreq}</changefreq>
                <priority>{$priority}</priority>
            </url>
        ";
    }

    /**
     * Robots.txt faylını generasiya edir
     *
     * Bu metod sistemdəki ümumi tənzimləmələr və SEO qaydalarına əsasən
     * robots.txt faylını yaradır. Host və Sitemap direktivi avtomatik əlavə olunur.
     */
    public function generateRobots(): string
    {
        return Cache::remember('robots_content', self::CACHE_TTL, function () {
            $content = "User-agent: *\n";

            // Qadağan edilmiş yollar
            $disallowPaths = $this->getDisallowedPaths();
            foreach ($disallowPaths as $path) {
                $content .= "Disallow: {$path}\n";
            }

            // İcazə verilən yollar
            $allowPaths = $this->getAllowedPaths();
            foreach ($allowPaths as $path) {
                $content .= "Allow: {$path}\n";
            }

            // Host direktivi
            $content .= "\nHost: " . config('app.url') . "\n";

            // Sitemap direktivi
            $content .= "Sitemap: " . url('sitemap.xml') . "\n";

            // Fiziki olaraq faylı saxlayırıq
            Storage::disk('public')->put('robots.txt', $content);

            return $content;
        });
    }

    /**
     * Qadağan edilmiş yolları qaytarır
     *
     * Sistemin təhlükəsizliyi üçün gizli qalmalı olan
     * bütün URL pattern-lərini buraya əlavə edirik.
     */
    protected function getDisallowedPaths(): array
    {
        return [
            '/admin/',
            '/api/',
            '/login',
            '/register',
            '/password/*',
            '/*.json',
            '/*.xml',
            '/storage/',
            '/tmp/',
            '/*.php$',
            '/cgi-bin/',
            '/?*sort=',
            '/?*filter=',
            '/?*page=',
            '/search?*'
        ];
    }

    /**
     * İcazə verilən xüsusi yolları qaytarır
     *
     * Qadağan edilmiş qovluqlarda olan, amma ictimai olmalı
     * olan URL pattern-lərini buraya əlavə edirik.
     */
    protected function getAllowedPaths(): array
    {
        return [
            '/storage/images/',
            '/storage/products/',
            '/storage/categories/',
            '/api/public/*'
        ];
    }

    /**
     * Sitemap indexini generasiya edir
     *
     * Əgər saytda çoxlu məlumat varsa, bir neçə sitemap faylına
     * bölmək və onları index altında birləşdirmək lazım ola bilər.
     */
    public function generateSitemapIndex(): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        // Əsas sitemap
        $xml .= $this->generateSitemapIndexEntry('sitemap.xml');

        // Kateqoriyalar üçün sitemap
        if ($this->needsCategorySitemap()) {
            $xml .= $this->generateSitemapIndexEntry('sitemap-categories.xml');
        }

        // Məhsullar üçün sitemap
        if ($this->needsProductSitemap()) {
            $xml .= $this->generateSitemapIndexEntry('sitemap-products.xml');
        }

        $xml .= '</sitemapindex>';

        Storage::disk('public')->put('sitemap-index.xml', $xml);

        return $xml;
    }

    /**
     * Sitemap index entry yaradır
     */
    protected function generateSitemapIndexEntry(string $filename): string
    {
        $url = url($filename);
        $lastmod = now()->toAtomString();

        return "
            <sitemap>
                <loc>{$url}</loc>
                <lastmod>{$lastmod}</lastmod>
            </sitemap>
        ";
    }

    /**
     * Kateqoriyalar üçün ayrı sitemap lazım olub-olmadığını yoxlayır
     */
    protected function needsCategorySitemap(): bool
    {
        // Məsələn, kateqoriyaların sayı 1000-dən çoxdursa
        return false; // Hələlik false qaytarırıq
    }

    /**
     * Məhsullar üçün ayrı sitemap lazım olub-olmadığını yoxlayır
     */
    protected function needsProductSitemap(): bool
    {
        // Məsələn, məhsulların sayı 1000-dən çoxdursa
        return false; // Hələlik false qaytarırıq
    }

    /**
     * Cache-i təmizləyir
     */
    public function clearCache(): void
    {
        Cache::forget('sitemap_content');
        Cache::forget('robots_content');
    }
}
