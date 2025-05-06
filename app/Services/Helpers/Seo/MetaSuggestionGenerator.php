<?php

namespace App\Services\Helpers\Seo;

use App\Models\Seo;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class MetaSuggestionGenerator
{
    /**
     * SEO yaxşılaşdırma təkliflərini generasiya edir
     */
    public function generate(Seo $seo): Collection
    {
        $suggestions = collect();

        // Title əsasında təkliflər
        $this->generateTitleBasedSuggestions($seo, $suggestions);

        // Description əsasında təkliflər
        $this->generateDescriptionBasedSuggestions($seo, $suggestions);

        // Sosial media təklifləri
        $this->generateSocialMediaSuggestions($seo, $suggestions);

        // Texniki təkliflər
        $this->generateTechnicalSuggestions($seo, $suggestions);

        return $suggestions->sortByDesc('priority');
    }

    /**
     * Title əsasında təkliflər generasiya edir
     */
    protected function generateTitleBasedSuggestions(Seo $seo, Collection $suggestions): void
    {
        $title = $seo->basic_meta['title'] ?? null;

        if ($title) {
            // OpenGraph title təklifi
            if (empty($seo->open_graph['og:title'])) {
                $ogTitle = $this->generateSocialTitle($title);
                $suggestions->push([
                    'type' => 'og:title',
                    'content' => $ogTitle,
                    'group' => 'open_graph',
                    'source' => 'title',
                    'priority' => 'high',
                    'message' => 'OpenGraph title avtomatik generasiya edildi'
                ]);
            }

            // Twitter title təklifi
            if (empty($seo->twitter['twitter:title'])) {
                $twitterTitle = $this->generateSocialTitle($title);
                $suggestions->push([
                    'type' => 'twitter:title',
                    'content' => $twitterTitle,
                    'group' => 'twitter',
                    'source' => 'title',
                    'priority' => 'high',
                    'message' => 'Twitter title avtomatik generasiya edildi'
                ]);
            }

            // Title uzunluğu təklifi
            $titleLength = mb_strlen($title);
            if ($titleLength < 30 || $titleLength > 60) {
                $suggestions->push([
                    'type' => 'title_length',
                    'group' => 'basic_meta',
                    'priority' => 'high',
                    'message' => 'Title uzunluğu optimal deyil. 30-60 simvol arası olmalıdır.',
                    'current_length' => $titleLength
                ]);
            }

            // Açar söz analizi
            if (!$this->containsKeywords($title)) {
                $suggestions->push([
                    'type' => 'title_keywords',
                    'group' => 'basic_meta',
                    'priority' => 'medium',
                    'message' => 'Title-da əsas açar sözlər istifadə edilməyib'
                ]);
            }
        }
    }

    /**
     * Description əsasında təkliflər generasiya edir
     */
    protected function generateDescriptionBasedSuggestions(Seo $seo, Collection $suggestions): void
    {
        $description = $seo->basic_meta['description'] ?? null;

        if ($description) {
            // OpenGraph description təklifi
            if (empty($seo->open_graph['og:description'])) {
                $ogDesc = $this->generateSocialDescription($description);
                $suggestions->push([
                    'type' => 'og:description',
                    'content' => $ogDesc,
                    'group' => 'open_graph',
                    'source' => 'description',
                    'priority' => 'high',
                    'message' => 'OpenGraph description avtomatik generasiya edildi'
                ]);
            }

            // Twitter description təklifi
            if (empty($seo->twitter['twitter:description'])) {
                $twitterDesc = $this->generateSocialDescription($description);
                $suggestions->push([
                    'type' => 'twitter:description',
                    'content' => $twitterDesc,
                    'group' => 'twitter',
                    'source' => 'description',
                    'priority' => 'high',
                    'message' => 'Twitter description avtomatik generasiya edildi'
                ]);
            }

            // Description uzunluğu təklifi
            $descLength = mb_strlen($description);
            if ($descLength < 120 || $descLength > 160) {
                $suggestions->push([
                    'type' => 'description_length',
                    'group' => 'basic_meta',
                    'priority' => 'high',
                    'message' => 'Description uzunluğu optimal deyil. 120-160 simvol arası olmalıdır.',
                    'current_length' => $descLength
                ]);
            }

            // Title ilə əlaqə yoxlaması
            if (!$this->titleDescriptionRelation($seo->basic_meta['title'] ?? '', $description)) {
                $suggestions->push([
                    'type' => 'title_description_relation',
                    'group' => 'basic_meta',
                    'priority' => 'medium',
                    'message' => 'Description-da title-dan heç bir açar söz istifadə edilməyib'
                ]);
            }
        }
    }

    /**
     * Sosial media təklifləri generasiya edir
     */
    protected function generateSocialMediaSuggestions(Seo $seo, Collection $suggestions): void
    {
        // OpenGraph image təklifi
        if (empty($seo->open_graph['og:image'])) {
            $suggestions->push([
                'type' => 'og:image',
                'group' => 'open_graph',
                'priority' => 'high',
                'message' => 'OpenGraph şəkli əlavə edilməyib'
            ]);
        }

        // Twitter Card type təklifi
        if (empty($seo->twitter['twitter:card'])) {
            $suggestions->push([
                'type' => 'twitter:card',
                'content' => 'summary_large_image',
                'group' => 'twitter',
                'priority' => 'medium',
                'message' => 'Twitter Card növü təyin edilməyib'
            ]);
        }
    }

    /**
     * Texniki təkliflər generasiya edir
     */
    protected function generateTechnicalSuggestions(Seo $seo, Collection $suggestions): void
    {
        // Canonical URL təklifi
        if (empty($seo->technical['canonical'])) {
            $suggestions->push([
                'type' => 'canonical',
                'content' => $seo->url,
                'group' => 'technical',
                'priority' => 'high',
                'message' => 'Canonical URL təyin edilməyib'
            ]);
        }

        // Robots direktivi təklifi
        if (empty($seo->technical['robots'])) {
            $suggestions->push([
                'type' => 'robots',
                'content' => 'index, follow',
                'group' => 'technical',
                'priority' => 'medium',
                'message' => 'Robots direktivi təyin edilməyib'
            ]);
        }

        // Language tag təklifi
        if (empty($seo->technical['language'])) {
            $suggestions->push([
                'type' => 'language',
                'content' => app()->getLocale(),
                'group' => 'technical',
                'priority' => 'medium',
                'message' => 'Dil tag-ı təyin edilməyib'
            ]);
        }
    }

    /**
     * Sosial media üçün title generasiya edir
     */
    protected function generateSocialTitle(string $title): string
    {
        // Title-ı təmizləyirik və qısaldırıq
        return Str::limit(
            str_replace(['-', '_', '|'], ' ', $title),
            95,
            '...'
        );
    }

    /**
     * Sosial media üçün description generasiya edir
     */
    protected function generateSocialDescription(string $description): string
    {
        // Description-ı təmizləyirik və qısaldırıq
        return Str::limit(
            preg_replace('/\s+/', ' ', $description),
            200,
            '...'
        );
    }

    /**
     * Title və Description arasında əlaqəni yoxlayır
     */
    protected function titleDescriptionRelation(string $title, string $description): bool
    {
        $titleWords = array_filter(
            explode(' ', mb_strtolower($title)),
            fn($word) => mb_strlen($word) > 3
        );

        $descriptionText = mb_strtolower($description);

        foreach ($titleWords as $word) {
            if (mb_strpos($descriptionText, $word) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Mətnin açar sözlər ehtiva etdiyini yoxlayır
     */
    protected function containsKeywords(string $text): bool
    {
        // Burada açar sözlərin axtarışı üçün məntiq əlavə edilə bilər
        // Məsələn, dildən asılı olaraq ümumi açar sözlər siyahısı və ya
        // daha mürəkkəb NLP alqoritmləri istifadə edilə bilər
        return true;
    }
}
