<?php

namespace App\Traits\Model;

use App\Models\SeoLink;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Str;

trait HasSeoLink
{
    /**
     * Boot the trait.
     */
    protected static function bootHasSeoLink(): void
    {
        static::created(function ($model) {
            $model->handleSeoLinkCreation();
        });

        static::updated(function ($model) {
            if ($model->shouldUpdateSeoLink()) {
                $model->handleSeoLinkUpdate();
            }
        });

        static::deleted(function ($model) {
            $model->seoLink?->delete();
        });
    }

    /**
     * Initialize the trait
     */
    public function initializeHasSeoLink(): void
    {
        if (!isset($this->appends)) {
            $this->appends = [];
        }

        $this->appends[] = 'seoMetaTags';
    }

    /**
     * Get the SEO link relationship
     */
    public function seoLink(): MorphOne
    {
        return $this->morphOne(SeoLink::class, 'seoable');
    }

    /**
     * Get SEO meta tags attribute
     */
    public function getSeoMetaTagsAttribute(): array
    {
        return cache()->remember(
            $this->getSeoLinkCacheKey(),
            now()->addHour(),
            fn () => $this->generateSeoMetaTagsData()
        );
    }

    /**
     * Check if SEO link should be updated
     */
    protected function shouldUpdateSeoLink(): bool
    {
        return $this->isDirty('translates') ||
            $this->isDirty('slug') ||
            $this->isDirty('photo_path');
    }

    /**
     * Handle SEO link creation
     */
    protected function handleSeoLinkCreation(): void
    {
        $this->seoLink()->create($this->prepareSeoLinkData());
        $this->clearSeoCache();
    }

    /**
     * Handle SEO link update
     */
    protected function handleSeoLinkUpdate(): void
    {
        if (!$this->seoLink) {
            $this->handleSeoLinkCreation();
            return;
        }

        $this->seoLink->update($this->prepareSeoLinkData());
        $this->clearSeoCache();
    }

    /**
     * Prepare SEO link data
     */
    protected function prepareSeoLinkData(): array
    {
        $defaultLocale = config('app.fallback_locale', 'az');
        $translates = $this->translates ?? [];
        $defaultTranslate = $translates[$defaultLocale] ?? [];

        return [
            'url' => $this->generateSeoUrl(),
            'basic_meta' => [
                'title' => $this->generateSeoTitle($defaultTranslate),
                'description' => $this->generateSeoDescription($defaultTranslate),
                'keywords' => $this->generateSeoKeywords($defaultTranslate),
                'robots' => $this->getDefaultRobotsRules()
            ],
            'open_graph' => [
                'og:title' => $defaultTranslate['name'] ?? '',
                'og:description' => Str::limit($defaultTranslate['description'] ?? '', 200),
                'og:type' => $this->getOpenGraphType(),
                'og:image' => $this->getSeoImageKey() ?? '',
                'og:url' => url($this->generateSeoUrl())
            ],
            'twitter' => [
                'twitter:card' => 'summary_large_image',
                'twitter:title' => $defaultTranslate['name'] ?? '',
                'twitter:description' => Str::limit($defaultTranslate['description'] ?? '', 200),
                'twitter:image' => $this->getSeoImageKey() ?? ''
            ],
            'technical' => [
                'canonical' => url($this->generateSeoUrl()),
                'language' => $defaultLocale,
                'author' => config('app.name'),
                'content-type' => 'text/html; charset=utf-8'
            ],
            'is_active' => true,
            'is_sitemap' => true,
            'sitemap_priority' => $this->getSitemapPriority(),
            'sitemap_frequency' => $this->getSitemapFrequency()
        ];
    }

    /**
     * Generate SEO URL
     */
    protected function generateSeoUrl(): string
    {
        $prefix = $this->getSeoUrlPrefix();
        return trim("{$prefix}/{$this->slug}", '/');
    }

    /**
     * Get SEO URL prefix
     */
    protected function getSeoUrlPrefix(): string
    {
        return '';
    }

    /**
     * Get SEO Image Key
     */
    protected function getSeoImageKey(): string
    {
        return 'photo';
    }

    /**
     * Generate SEO title
     */
    protected function generateSeoTitle(array $translate): string
    {
        $title = $translate['name'] ?? '';
        $siteName = config('app.name');

        return $title ? "{$title} | {$siteName}" : $siteName;
    }

    /**
     * Generate SEO description
     */
    protected function generateSeoDescription(array $translate): string
    {
        return Str::limit($translate['description'] ?? '', 160);
    }

    /**
     * Generate SEO keywords
     */
    protected function generateSeoKeywords(array $translate): string
    {
        $keywords = [];

        if (!empty($translate['name'])) {
            $keywords[] = $translate['name'];
            $keywords = array_merge(
                $keywords,
                explode(' ', Str::slug($translate['name'], ' '))
            );
        }

        return implode(', ', array_unique($keywords));
    }

    /**
     * Get default robots rules
     */
    protected function getDefaultRobotsRules(): array
    {
        return [
            'index' => true,
            'follow' => true,
            'max-snippet' => -1,
            'max-image-preview' => 'large',
            'max-video-preview' => -1
        ];
    }

    /**
     * Get Open Graph type
     */
    protected function getOpenGraphType(): string
    {
        return 'website';
    }

    /**
     * Get sitemap priority
     */
    protected function getSitemapPriority(): string
    {
        return $this->parent_id === 0 ? '0.8' : '0.6';
    }

    /**
     * Get sitemap frequency
     */
    protected function getSitemapFrequency(): string
    {
        return 'weekly';
    }

    /**
     * Generate SEO meta tags data
     */
    protected function generateSeoMetaTagsData(): array
    {
        if (!$this->seoLink) {
            $this->handleSeoLinkCreation();
            return [];
        }

        return [
            'url' => $this->seoLink->url,
            'meta' => [
                'title' => $this->seoLink->basic_meta['title'] ?? '',
                'description' => $this->seoLink->basic_meta['description'] ?? '',
                'keywords' => $this->seoLink->basic_meta['keywords'] ?? '',
                'robots' => $this->seoLink->basic_meta['robots'] ?? [],
            ],
            'og' => [
                'title' => $this->seoLink->open_graph['og:title'] ?? '',
                'description' => $this->seoLink->open_graph['og:description'] ?? '',
                'image' => $this->seoLink->open_graph['og:image'] ?? '',
                'type' => $this->seoLink->open_graph['og:type'] ?? '',
                'url' => $this->seoLink->open_graph['og:url'] ?? '',
            ],
            'twitter' => [
                'card' => $this->seoLink->twitter['twitter:card'] ?? '',
                'title' => $this->seoLink->twitter['twitter:title'] ?? '',
                'description' => $this->seoLink->twitter['twitter:description'] ?? '',
                'image' => $this->seoLink->twitter['twitter:image'] ?? '',
            ],
            'technical' => $this->seoLink->technical ?? [],
            'score' => $this->seoLink->score ?? 0,
            'analysis' => $this->seoLink->analysis ?? [],
            'sitemap' => [
                'is_active' => $this->seoLink->is_sitemap,
                'priority' => $this->seoLink->sitemap_priority,
                'frequency' => $this->seoLink->sitemap_frequency
            ]
        ];
    }

    /**
     * Get SEO link cache key
     */
    protected function getSeoLinkCacheKey(): string
    {
        return "seo_meta_tags_{$this->getTable()}_{$this->getKey()}";
    }

    /**
     * Clear SEO cache
     */
    protected function clearSeoCache(): void
    {
        cache()->forget($this->getSeoLinkCacheKey());
    }

    /**
     * Helper methods for easy access
     */
    public function getSeoTitle(?string $locale = null): string
    {
        return $this->seoMetaTags['meta']['title'] ?? '';
    }

    public function getSeoDescription(?string $locale = null): string
    {
        return $this->seoMetaTags['meta']['description'] ?? '';
    }

    public function getSeoKeywords(?string $locale = null): string
    {
        return $this->seoMetaTags['meta']['keywords'] ?? '';
    }

    public function getSeoImage(): ?string
    {
        return $this->seoMetaTags['og']['image'] ?? null;
    }

    public function getSeoScore(): int
    {
        return $this->seoMetaTags['score'] ?? 0;
    }

    public function getSeoAnalysis(): array
    {
        return $this->seoMetaTags['analysis'] ?? [];
    }
}
