<?php

namespace App\Models;

use App\Enums\SeoTypeEnum;
use App\Services\Helpers\Seo\SeoScoreCalculator;
use App\Traits\Model\HasCode;
use App\Traits\Model\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class SeoLink extends BaseModel
{
    use HasCode, HasUuid;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'url',
        'seoable_type',
        'seoable_id',
        'basic_meta',
        'open_graph',
        'twitter',
        'technical',
        'custom_tags',
        'score',
        'analysis',
        'history',
        'validation_errors',
        'is_sitemap',
        'sitemap_priority',
        'sitemap_frequency',
        'is_active'
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'basic_meta' => 'json',
        'open_graph' => 'json',
        'twitter' => 'json',
        'technical' => 'json',
        'custom_tags' => 'json',
        'analysis' => 'json',
        'history' => 'json',
        'is_active' => 'boolean',
        'is_sitemap' => 'boolean',
        'score' => 'integer'
    ];

    protected $appends = ['seoable_type_text'];

    /**
     * Meta tag qrupları üçün default strukturlar
     */
    protected array $defaultStructures = [
        'basic_meta' => [
            'title' => null,
            'description' => null,
            'keywords' => null,
            'robots' => [
                'index' => true,
                'follow' => true
            ],
            'viewport' => 'width=device-width, initial-scale=1.0'
        ],
        'open_graph' => [
            'og:title' => null,
            'og:description' => null,
            'og:image' => null,
            'og:type' => 'website',
            'og:url' => null,
            'og:site_name' => null
        ],
        'twitter' => [
            'twitter:card' => 'summary_large_image',
            'twitter:title' => null,
            'twitter:description' => null,
            'twitter:image' => null,
            'twitter:site' => null
        ],
        'technical' => [
            'canonical' => null,
            'content-type' => 'text/html; charset=utf-8',
            'language' => 'az',
            'author' => null,
            'copyright' => null
        ]
    ];

    /**
     * Məcburi meta taglar siyahısı
     */
    protected array $requiredMetaTags = [
        'basic_meta' => ['title', 'description'],
        'open_graph' => ['og:title', 'og:description'],
        'twitter' => ['twitter:title', 'twitter:description']
    ];

    protected static function booted(): void
    {
        static::saving(function ($model) {
            // Hər dəfə model save ediləndə score-u yenidən hesablayaq
            $calculator = new SeoScoreCalculator();
            $analysis = $calculator->calculate($model);
            $model->score = $analysis['score'];
            $model->analysis = $analysis;
        });
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    /**
     * Morphable əlaqə - digər modellərlə əlaqə
     */
    public function seoable(): MorphTo
    {
        return $this->morphTo();
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /**
     * Aktiv SEO yazılarını filtrlənmək üçün scope
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Sitemap-də görünən yazıları filtrlənmək üçün scope
     */
    public function scopeForSitemap(Builder $query): Builder
    {
        return $query->where('is_sitemap', true)
            ->where('is_active', true);
    }

    /**
     * URL-ə görə axtarış üçün scope
     */
    public function scopeByUrl(Builder $query, string $url): Builder
    {
        return $query->where('url', $url);
    }

    /*
    |--------------------------------------------------------------------------
    | META TAG OPERATIONS
    |--------------------------------------------------------------------------
    */

    /**
     * Meta tag əlavə etmək üçün metod
     */
    public function addMetaTag(string $group, string $type, string $content): void
    {
        // Meta tagın hansı qrupa aid olduğunu yoxlayırıq
        if (!isset($this->defaultStructures[$group])) {
            throw new \InvalidArgumentException("Invalid meta tag group: {$group}");
        }

        // Meta data array-i alırıq və ya yeni yaradırıq
        $metaData = $this->{$group} ?? [];

        // Kontenti təmizləyirik
        $content = $this->sanitizeMetaContent($content);

        // Meta tagı əlavə edirik
        $metaData[$type] = $content;

        // Modeli yeniləyirik
        $this->{$group} = $metaData;

        // Tarixçəyə əlavə edirik
        $this->addToHistory('add_tag', [
            'group' => $group,
            'type' => $type,
            'content' => $content
        ]);
    }

    /**
     * Meta tag silmək üçün metod
     */
    public function removeMetaTag(string $group, string $type): void
    {
        if (!isset($this->{$group})) {
            return;
        }

        $metaData = $this->{$group};

        // Məcburi tag-ı silməyə çalışırlarsa xəta qaytarırıq
        if (isset($this->requiredMetaTags[$group]) &&
            in_array($type, $this->requiredMetaTags[$group])) {
            throw new \InvalidArgumentException("Cannot remove required meta tag: {$type}");
        }

        unset($metaData[$type]);
        $this->{$group} = $metaData;

        $this->addToHistory('remove_tag', [
            'group' => $group,
            'type' => $type
        ]);
    }

    /**
     * Meta tag məzmununu yeniləmək üçün metod
     */
    public function updateMetaTag(string $group, string $type, string $content): void
    {
        $this->addMetaTag($group, $type, $content);

        $this->addToHistory('update_tag', [
            'group' => $group,
            'type' => $type,
            'content' => $content
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | PREVIEW GENERATORS
    |--------------------------------------------------------------------------
    */

    /**
     * HTML meta taglarını generasiya etmək
     */
    public function generateMetaTags(): string
    {
        $html = '';

        // Basic meta tags
        if ($this->basic_meta) {
            if (!empty($this->basic_meta['title'])) {
                $html .= "<title>{$this->basic_meta['title']}</title>\n";
            }

            foreach ($this->basic_meta as $name => $content) {
                if ($name !== 'title' && $content) {
                    if ($name === 'robots') {
                        $content = $this->formatRobotsContent($content);
                    }
                    $html .= "<meta name=\"{$name}\" content=\"{$content}\">\n";
                }
            }
        }

        // OpenGraph tags
        if ($this->open_graph) {
            foreach ($this->open_graph as $property => $content) {
                if ($content) {
                    $html .= "<meta property=\"{$property}\" content=\"{$content}\">\n";
                }
            }
        }

        // Twitter tags
        if ($this->twitter) {
            foreach ($this->twitter as $name => $content) {
                if ($content) {
                    $html .= "<meta name=\"{$name}\" content=\"{$content}\">\n";
                }
            }
        }

        // Technical tags
        if ($this->technical) {
            foreach ($this->technical as $name => $content) {
                if ($content) {
                    if ($name === 'canonical') {
                        $html .= "<link rel=\"canonical\" href=\"{$content}\">\n";
                    } else {
                        $html .= "<meta name=\"{$name}\" content=\"{$content}\">\n";
                    }
                }
            }
        }

        // Custom tags
        if ($this->custom_tags) {
            foreach ($this->custom_tags as $tag) {
                $html .= "<meta {$tag['type']}=\"{$tag['name']}\" content=\"{$tag['content']}\">\n";
            }
        }

        return $html;
    }

    /**
     * Google SERP preview üçün məlumatları hazırlamaq
     */
    public function getGooglePreviewData(): array
    {
        return [
            'title' => $this->basic_meta['title'] ?? '',
            'description' => $this->basic_meta['description'] ?? '',
            'url' => $this->url
        ];
    }

    /**
     * Sosial media preview üçün məlumatları hazırlamaq
     */
    public function getSocialPreviewData(): array
    {
        return [
            'og' => [
                'title' => $this->open_graph['og:title'] ?? '',
                'description' => $this->open_graph['og:description'] ?? '',
                'image' => $this->open_graph['og:image'] ?? '',
                'type' => $this->open_graph['og:type'] ?? 'website'
            ],
            'twitter' => [
                'title' => $this->twitter['twitter:title'] ?? '',
                'description' => $this->twitter['twitter:description'] ?? '',
                'image' => $this->twitter['twitter:image'] ?? '',
                'card' => $this->twitter['twitter:card'] ?? 'summary_large_image'
            ]
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | HELPER METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Meta tag kontentini təmizləmək və format etmək
     */
    protected function sanitizeMetaContent(string $content): string
    {
        // HTML tagları təmizləyirik
        $content = strip_tags($content);

        // Xüsusi simvolları encode edirik
        $content = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');

        // Artıq boşluqları təmizləyirik
        return trim($content);
    }

    /**
     * Robots direktivi üçün kontenti format etmək
     */
    protected function formatRobotsContent(array $robots): string
    {
        $directives = [];

        if (isset($robots['index'])) {
            $directives[] = $robots['index'] ? 'index' : 'noindex';
        }

        if (isset($robots['follow'])) {
            $directives[] = $robots['follow'] ? 'follow' : 'nofollow';
        }

        return implode(', ', $directives);
    }

    /**
     * Tarixçəyə yeni qeyd əlavə etmək
     */
    protected function addToHistory(string $action, array $data): void
    {
        $history = $this->history ?? [];
        $history[] = [
            'action' => $action,
            'data' => $data,
            'user_id' => auth()->id(),
            'user_name' => auth()->user()?->name,
            'timestamp' => now()->toIso8601String()
        ];
        $this->history = $history;
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // URL-i avtomatik format edirik
        static::saving(function (self $model) {
            if ($model->isDirty('url')) {
                $model->url = Str::lower(trim($model->url, '/'));
            }
        });
    }

    /**
     * @return Attribute
     */
    public function seoableTypeText(): Attribute
    {
        return new Attribute(
            get: function () {
                return $this->seoable_type ? SeoTypeEnum::getDescription($this->seoable_type) : '-';
            }
        );
    }
}
