<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SeoLinkResource extends JsonResource
{
    /**
     * SEO məlumatlarını API üçün transformasiya edir
     */
    public function toArray(Request $request): array
    {
        // Əsas məlumatlar
        $data = [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'url' => $this->url,
            'seoable' => $this->seoable,
            'seoable_type' => $this->seoable_type,
            'seoable_type_text' => $this->seoable_type_text,
            'seoable_id' => $this->seoable_id,

            // Meta taglar qrupu
            'meta_tags' => [
                'basic' => $this->formatBasicMeta(),
                'open_graph' => $this->formatOpenGraph(),
                'twitter' => $this->formatTwitter(),
                'technical' => $this->formatTechnical(),
                'custom' => $this->custom_tags
            ],

            // Preview məlumatları
            'previews' => [
                'google' => [
                    'title' => $this->basic_meta['title'] ?? null,
                    'description' => $this->basic_meta['description'] ?? null,
                    'url' => $this->url
                ],
                'social' => [
                    'open_graph' => [
                        'title' => $this->open_graph['og:title'] ?? null,
                        'description' => $this->open_graph['og:description'] ?? null,
                        'image' => $this->open_graph['og:image'] ?? null,
                        'type' => $this->open_graph['og:type'] ?? 'website'
                    ],
                    'twitter' => [
                        'title' => $this->twitter['twitter:title'] ?? null,
                        'description' => $this->twitter['twitter:description'] ?? null,
                        'image' => $this->twitter['twitter:image'] ?? null,
                        'card' => $this->twitter['twitter:card'] ?? 'summary_large_image'
                    ]
                ],
                'html' => $this->generateHtmlPreview()
            ],

            // Analitika məlumatları
            'analytics' => [
                'score' => $this->score,
                'analysis' => $this->analysis,
                'suggestions' => $this->formatSuggestions()
            ],

            // Sitemap məlumatları
            'sitemap_config' => [
                'is_sitemap' => $this->is_sitemap,
                'priority' => $this->sitemap_priority,
                'frequency' => $this->sitemap_frequency
            ],

            // Status məlumatları
            'is_active' => $this->is_active,

            // Tarixçə və audit məlumatları
            'history' => $this->formatHistory(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'created_by_name' => $this->created_by_name,
            'updated_by_name' => $this->updated_by_name
        ];

        return $data;
    }

    /**
     * Basic meta tagları formatlaşdırır
     */
    protected function formatBasicMeta(): array
    {
        $basicMeta = $this->basic_meta ?? [];

        return [
            'title' => [
                'content' => $basicMeta['title'] ?? null,
                'length' => mb_strlen($basicMeta['title'] ?? ''),
                'status' => $this->getMetaStatus('title', $basicMeta['title'] ?? null)
            ],
            'description' => [
                'content' => $basicMeta['description'] ?? null,
                'length' => mb_strlen($basicMeta['description'] ?? ''),
                'status' => $this->getMetaStatus('description', $basicMeta['description'] ?? null)
            ],
            'keywords' => [
                'content' => $basicMeta['keywords'] ?? null,
                'count' => count(explode(',', $basicMeta['keywords'] ?? '')),
                'status' => $this->getMetaStatus('keywords', $basicMeta['keywords'] ?? null)
            ],
            'robots' => $basicMeta['robots'] ?? null,
            'viewport' => $basicMeta['viewport'] ?? null
        ];
    }

    /**
     * OpenGraph taglarını formatlaşdırır
     */
    protected function formatOpenGraph(): array
    {
        $og = $this->open_graph ?? [];

        return [
            'title' => [
                'content' => $og['og:title'] ?? null,
                'length' => mb_strlen($og['og:title'] ?? ''),
                'status' => $this->getMetaStatus('og:title', $og['og:title'] ?? null)
            ],
            'description' => [
                'content' => $og['og:description'] ?? null,
                'length' => mb_strlen($og['og:description'] ?? ''),
                'status' => $this->getMetaStatus('og:description', $og['og:description'] ?? null)
            ],
            'image' => [
                'url' => $og['og:image'] ?? null,
                'status' => $this->getMetaStatus('og:image', $og['og:image'] ?? null)
            ],
            'type' => $og['og:type'] ?? 'website'
        ];
    }

    /**
     * Twitter Card taglarını formatlaşdırır
     */
    protected function formatTwitter(): array
    {
        $twitter = $this->twitter ?? [];

        return [
            'card' => $twitter['twitter:card'] ?? 'summary_large_image',
            'title' => [
                'content' => $twitter['twitter:title'] ?? null,
                'length' => mb_strlen($twitter['twitter:title'] ?? ''),
                'status' => $this->getMetaStatus('twitter:title', $twitter['twitter:title'] ?? null)
            ],
            'description' => [
                'content' => $twitter['twitter:description'] ?? null,
                'length' => mb_strlen($twitter['twitter:description'] ?? ''),
                'status' => $this->getMetaStatus('twitter:description', $twitter['twitter:description'] ?? null)
            ],
            'image' => [
                'url' => $twitter['twitter:image'] ?? null,
                'status' => $this->getMetaStatus('twitter:image', $twitter['twitter:image'] ?? null)
            ]
        ];
    }

    /**
     * Texniki meta tagları formatlaşdırır
     */
    protected function formatTechnical(): array
    {
        $technical = $this->technical ?? [];

        return [
            'canonical' => [
                'url' => $technical['canonical'] ?? null,
                'status' => $this->getMetaStatus('canonical', $technical['canonical'] ?? null)
            ],
            'language' => $technical['language'] ?? app()->getLocale(),
            'content_type' => $technical['content-type'] ?? 'text/html; charset=utf-8'
        ];
    }

    /**
     * Meta tag statusunu təyin edir
     */
    protected function getMetaStatus(string $type, ?string $content): string
    {
        if (empty($content)) {
            return 'missing';
        }

        $length = mb_strlen($content);

        switch ($type) {
            case 'title':
                return ($length >= 50 && $length <= 60) ? 'optimal' : 'warning';

            case 'description':
                return ($length >= 150 && $length <= 160) ? 'optimal' : 'warning';

            case 'og:title':
                return ($length >= 60 && $length <= 95) ? 'optimal' : 'warning';

            case 'og:description':
            case 'twitter:description':
                return ($length >= 120 && $length <= 200) ? 'optimal' : 'warning';

            case 'twitter:title':
                return ($length >= 50 && $length <= 70) ? 'optimal' : 'warning';

            case 'og:image':
            case 'twitter:image':
            case 'canonical':
                return filter_var($content, FILTER_VALIDATE_URL) ? 'optimal' : 'warning';

            default:
                return 'normal';
        }
    }

    /**
     * Təklifləri formatlaşdırır
     */
    protected function formatSuggestions(): array
    {
        if (!isset($this->analysis['suggestions'])) {
            return [];
        }

        return collect($this->analysis['suggestions'])
            ->map(function ($suggestion) {
                return [
                    'type' => @$suggestion['type'],
                    'message' => @$suggestion['message'],
                    'priority' => @$suggestion['priority'],
                    'group' => @$suggestion['group']
                ];
            })
            ->sortByDesc('priority')
            ->values()
            ->toArray();
    }

    /**
     * Tarixçəni formatlaşdırır
     */
    protected function formatHistory(): array
    {
        return collect($this->history ?? [])
            ->map(function ($entry) {
                return [
                    'action' => $entry['action'],
                    'data' => $entry['data'],
                    'user' => [
                        'id' => $entry['user_id'],
                        'name' => $entry['user_name']
                    ],
                    'timestamp' => $entry['timestamp']
                ];
            })
            ->sortByDesc('timestamp')
            ->values()
            ->toArray();
    }

    protected function generateHtmlPreview(): string
    {
        $html = '';

        // Basic meta tags
        if (!empty($this->basic_meta['title'])) {
            $html .= "<title>{$this->basic_meta['title']}</title>\n";
        }
        if (!empty($this->basic_meta['description'])) {
            $html .= "<meta name=\"description\" content=\"{$this->basic_meta['description']}\">\n";
        }
        if (!empty($this->basic_meta['keywords'])) {
            $html .= "<meta name=\"keywords\" content=\"{$this->basic_meta['keywords']}\">\n";
        }

        // OpenGraph tags
        if (!empty($this->open_graph)) {
            foreach ($this->open_graph as $property => $content) {
                if ($content) {
                    $html .= "<meta property=\"{$property}\" content=\"{$content}\">\n";
                }
            }
        }

        // Twitter tags
        if (!empty($this->twitter)) {
            foreach ($this->twitter as $name => $content) {
                if ($content) {
                    $html .= "<meta name=\"{$name}\" content=\"{$content}\">\n";
                }
            }
        }

        // Technical tags
        if (!empty($this->technical)) {
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

        return $html;
    }
}
