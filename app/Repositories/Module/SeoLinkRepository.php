<?php

namespace App\Repositories\Module;

use App\Enums\SeoTypeEnum;
use App\Models\SeoLink;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class SeoLinkRepository extends BaseRepository
{
    // Cache-i aktivləşdiririk - SEO məlumatları tez-tez dəyişmir
    protected bool $useCache = true;

    // Cache müddəti - 1 saat
    protected int $cacheTtl = 3600;

    public function __construct(SeoLink $model)
    {
        parent::__construct($model);
        $this->with = ['seoable'];
    }

    /**
     * URL-ə görə SEO məlumatını tapmaq.
     * Bu metod ən çox istifadə edilən metoddur və cache-dən faydalanır.
     */
    public function findByUrl(string $url): ?SeoLink
    {
        return $this->executeWithCache("findByUrl.{$url}", function () use ($url) {
            return $this->model->byUrl($url)->first();
        });
    }

    /**
     * Sitemap üçün lazım olan aktiv SEO yazılarını almaq
     */
    public function getForSitemap(): Collection
    {
        return $this->executeWithCache('sitemap_data', function () {
            return $this->model->forSitemap()
                ->select(['url', 'sitemap_priority', 'sitemap_frequency', 'updated_at'])
                ->get();
        });
    }

    /**
     * Ana səhifə və ən vacib səhifələrin SEO məlumatlarını almaq
     */
    public function getPrioritySeoData(): Collection
    {
        return $this->executeWithCache('priority_seo_data', function () {
            return $this->model->where('sitemap_priority', '>=', '0.8')
                ->active()
                ->get();
        });
    }

    /**
     * Model tipinə görə SEO məlumatlarını almaq (məs: "post", "product")
     */
    public function findByModelType(string $type): Collection
    {
        return $this->executeWithCache("seo_by_type.{$type}", function () use ($type) {
            return $this->model->where('seoable_type', $type)
                ->active()
                ->get();
        });
    }

    /**
     * Xüsusi filterləri tətbiq etmək
     */
    protected function applyCustomFilters(Builder $query): Builder
    {
        // Score-a görə filtirləmə
        $query->when(request('min_score'), function ($q, $score) {
            return $q->where('score', '>=', $score);
        });

        // Meta tag varlığına görə filtirləmə
        $query->when(request('has_tag'), function ($q, $tag) {
            return $q->whereJsonContains("basic_meta->{$tag}", true);
        });

        // URL pattern-ə görə axtarış
        $query->when(request('url_pattern'), function ($q, $pattern) {
            return $q->where('url', 'LIKE', "%{$pattern}%");
        });

        // Tarix aralığına görə filtirləmə
        $query->when(request('date_range'), function ($q, $range) {
            if (!empty($range['from'])) {
                $q->whereDate('created_at', '>=', $range['from']);
            }
            if (!empty($range['to'])) {
                $q->whereDate('created_at', '<=', $range['to']);
            }
        });

        return $query;
    }

    /**
     * Bütün filter seçimlərini almaq
     */
    public function filters(): array
    {
        return [
            'score_ranges' => [
                ['id' => 80, 'name' => 'Əla (80+)'],
                ['id' => 60, 'name' => 'Yaxşı (60-79)'],
                ['id' => 40, 'name' => 'Orta (40-59)'],
                ['id' => 0, 'name' => 'Zəif (0-39)']
            ],
            'sitemap_frequencies' => [
                ['id' => 'always', 'name' => 'Həmişə'],
                ['id' => 'hourly', 'name' => 'Hər saat'],
                ['id' => 'daily', 'name' => 'Gündəlik'],
                ['id' => 'weekly', 'name' => 'Həftəlik'],
                ['id' => 'monthly', 'name' => 'Aylıq'],
                ['id' => 'yearly', 'name' => 'İllik']
            ],
            'content_types' => collect(SeoTypeEnum::getValues())->map(fn($i) => [
                'id' => $i,
                'name' => SeoTypeEnum::getDescription($i),
            ])
        ];
    }

    /**
     * Sistemdə olan kontent tiplərini almaq
     */
    protected function getContentTypes(): array
    {
        return $this->executeWithCache('seo_content_types', function () {
            return $this->model->select('seoable_type')
                ->distinct()
                ->pluck('seoable_type')
                ->map(fn($type) => [
                    'id' => $type,
                    'name' => ucfirst($type)
                ])
                ->toArray();
        });
    }

    /**
     * Cache-i təmizləmək üçün xüsusi metodlar
     */
    public function clearUrlCache(string $url): void
    {
        $this->clearCache("findByUrl.{$url}");
    }

    public function clearSitemapCache(): void
    {
        $this->clearCache('sitemap_data');
    }

    public function clearTypeCache(string $type): void
    {
        $this->clearCache("seo_by_type.{$type}");
    }

    /**
     * Statistika üçün lazım olan məlumatları almaq
     */
    public function getStatistics(): array
    {
        return $this->executeWithCache('seo_statistics', function () {
            return [
                'total_pages' => $this->model->count(),
                'active_pages' => $this->model->active()->count(),
                'avg_score' => $this->model->average('score'),
                'perfect_score' => $this->model->where('score', '>=', 80)->count(),
                'needs_improvement' => $this->model->where('score', '<', 40)->count(),
                'most_common_type' => $this->model->select('seoable_type')
                    ->groupBy('seoable_type')
                    ->orderByRaw('COUNT(*) DESC')
                    ->first()?->seoable_type
            ];
        }, [], 3600); // 1 saat cache
    }
}
