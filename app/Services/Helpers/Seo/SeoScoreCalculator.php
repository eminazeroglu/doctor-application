<?php

namespace App\Services\Helpers\Seo;

use App\Models\SeoLink;
use Illuminate\Support\Str;

class SeoScoreCalculator
{
    /**
     * Hər bir SEO komponenti üçün maksimum ballar
     */
    protected const MAX_SCORES = [
        'basic_meta' => [
            'title' => 20,       // Title-ın düzgünlüyü
            'description' => 20,  // Description-ın düzgünlüyü
            'keywords' => 10      // Açar sözlərin düzgünlüyü
        ],
        'social_media' => [
            'open_graph' => 15,   // OpenGraph tagların tamlığı
            'twitter' => 15       // Twitter Card tagların tamlığı
        ],
        'technical' => [
            'canonical' => 10,    // Canonical URL-in mövcudluğu
            'robots' => 5,        // Robots direktivinin düzgünlüyü
            'language' => 5       // Dil tag-ının mövcudluğu
        ]
    ];

    /**
     * SEO score-nu hesablayır və detallı analiz qaytarır
     */
    public function calculate(SeoLink $seo): array
    {
        // Əsas meta tagların analizi
        $basicMetaScore = $this->analyzeBasicMeta($seo->basic_meta);

        // Sosial media taglarının analizi
        $socialScore = $this->analyzeSocialMedia($seo->open_graph, $seo->twitter);

        // Texniki meta tagların analizi
        $technicalScore = $this->analyzeTechnical($seo->technical);

        // Ümumi xalı hesablayırıq
        $totalScore = array_sum([
            $basicMetaScore['score'],
            $socialScore['score'],
            $technicalScore['score']
        ]);

        // Təkmilləşdirmə təkliflərini hazırlayırıq
        $suggestions = $this->generateSuggestions([
            'basic_meta' => $basicMetaScore,
            'social' => $socialScore,
            'technical' => $technicalScore
        ]);

        return [
            'score' => $totalScore,
            'details' => [
                'basic_meta' => $basicMetaScore,
                'social' => $socialScore,
                'technical' => $technicalScore
            ],
            'suggestions' => $suggestions
        ];
    }

    /**
     * Əsas meta tagların analizi
     */
    protected function analyzeBasicMeta(?array $meta): array
    {
        $score = 0;
        $details = [];

        if (!$meta) {
            return ['score' => $score, 'details' => ['Əsas meta taglar mövcud deyil']];
        }

        // Title analizi
        if (!empty($meta['title'])) {
            $titleLength = mb_strlen($meta['title']);
            if ($titleLength >= 50 && $titleLength <= 60) {
                $score += self::MAX_SCORES['basic_meta']['title'];
                $details[] = ['type' => 'success', 'message' => 'Title uzunluğu idealdir'];
            } elseif ($titleLength > 30 && $titleLength < 70) {
                $score += self::MAX_SCORES['basic_meta']['title'] / 2;
                $details[] = ['type' => 'warning', 'message' => 'Title uzunluğu qəbul ediləndir, amma ideal deyil'];
            } else {
                $details[] = ['type' => 'error', 'message' => 'Title uzunluğu optimal deyil'];
            }
        } else {
            $details[] = ['type' => 'error', 'message' => 'Title mövcud deyil'];
        }

        // Description analizi
        if (!empty($meta['description'])) {
            $descLength = mb_strlen($meta['description']);
            if ($descLength >= 150 && $descLength <= 160) {
                $score += self::MAX_SCORES['basic_meta']['description'];
                $details[] = ['type' => 'success', 'message' => 'Description uzunluğu idealdir'];
            } elseif ($descLength > 120 && $descLength < 170) {
                $score += self::MAX_SCORES['basic_meta']['description'] / 2;
                $details[] = ['type' => 'warning', 'message' => 'Description uzunluğu qəbul ediləndir'];
            } else {
                $details[] = ['type' => 'error', 'message' => 'Description uzunluğu optimal deyil'];
            }
        } else {
            $details[] = ['type' => 'error', 'message' => 'Description mövcud deyil'];
        }

        // Keywords analizi
        if (!empty($meta['keywords'])) {
            $keywords = explode(',', $meta['keywords']);
            $keywordCount = count($keywords);

            if ($keywordCount >= 3 && $keywordCount <= 7) {
                $score += self::MAX_SCORES['basic_meta']['keywords'];
                $details[] = ['type' => 'success', 'message' => 'Açar sözlərin sayı optimaldır'];
            } elseif ($keywordCount > 7) {
                $score += self::MAX_SCORES['basic_meta']['keywords'] / 2;
                $details[] = ['type' => 'warning', 'message' => 'Açar sözlərin sayı həddindən çoxdur'];
            } else {
                $details[] = ['type' => 'error', 'message' => 'Açar sözlərin sayı azdır'];
            }
        } else {
            $details[] = ['type' => 'warning', 'message' => 'Keywords təyin edilməyib'];
        }

        return [
            'score' => $score,
            'details' => $details
        ];
    }

    /**
     * Sosial media taglarının analizi
     */
    protected function analyzeSocialMedia(?array $og, ?array $twitter): array
    {
        $score = 0;
        $details = [];

        // OpenGraph analizi
        if (isset($og) && is_array($og) && array_filter($og)) {  // array_filter boş arrayı yoxlayır
            $requiredOgTags = ['og:title', 'og:description', 'og:image'];
            $existingOgTags = array_intersect_key($og, array_flip($requiredOgTags));

            if (!empty($existingOgTags)) {
                $ogScore = (count($existingOgTags) / count($requiredOgTags)) * self::MAX_SCORES['social_media']['open_graph'];
                $score += $ogScore;

                if (count($existingOgTags) === count($requiredOgTags)) {
                    $details[] = ['type' => 'success', 'message' => 'Bütün OpenGraph tagları mövcuddur'];
                } else {
                    $details[] = ['type' => 'warning', 'message' => 'Bəzi OpenGraph tagları əskikdir'];
                }
            } else {
                $details[] = ['type' => 'error', 'message' => 'OpenGraph tagları mövcud deyil'];
            }
        } else {
            $details[] = ['type' => 'error', 'message' => 'OpenGraph tagları mövcud deyil'];
        }

        // Twitter Card analizi
        if (isset($twitter) && is_array($twitter) && array_filter($twitter)) {
            $requiredTwitterTags = ['twitter:card', 'twitter:title', 'twitter:description'];
            $existingTwitterTags = array_intersect_key($twitter, array_flip($requiredTwitterTags));

            if (!empty($existingTwitterTags)) {
                $twitterScore = (count($existingTwitterTags) / count($requiredTwitterTags)) * self::MAX_SCORES['social_media']['twitter'];
                $score += $twitterScore;

                if (count($existingTwitterTags) === count($requiredTwitterTags)) {
                    $details[] = ['type' => 'success', 'message' => 'Bütün Twitter Card tagları mövcuddur'];
                } else {
                    $details[] = ['type' => 'warning', 'message' => 'Bəzi Twitter Card tagları əskikdir'];
                }
            } else {
                $details[] = ['type' => 'error', 'message' => 'Twitter Card tagları mövcud deyil'];
            }
        } else {
            $details[] = ['type' => 'error', 'message' => 'Twitter Card tagları mövcud deyil'];
        }

        return [
            'score' => $score,
            'details' => $details
        ];
    }

    /**
     * Texniki meta tagların analizi
     */
    protected function analyzeTechnical(?array $technical): array
    {
        $score = 0;
        $details = [];

        if (!$technical) {
            return ['score' => $score, 'details' => ['Texniki meta taglar mövcud deyil']];
        }

        // Canonical URL analizi
        if (!empty($technical['canonical'])) {
            $score += self::MAX_SCORES['technical']['canonical'];
            $details[] = ['type' => 'success', 'message' => 'Canonical URL mövcuddur'];
        } else {
            $details[] = ['type' => 'warning', 'message' => 'Canonical URL təyin edilməyib'];
        }

        // Robots direktivi analizi
        if (!empty($technical['robots'])) {
            $score += self::MAX_SCORES['technical']['robots'];
            $details[] = ['type' => 'success', 'message' => 'Robots direktivi düzgün təyin edilib'];
        }

        // Dil tag-ı analizi
        if (!empty($technical['language'])) {
            $score += self::MAX_SCORES['technical']['language'];
            $details[] = ['type' => 'success', 'message' => 'Dil tag-ı mövcuddur'];
        }

        return [
            'score' => $score,
            'details' => $details
        ];
    }

    /**
     * Təkmilləşdirmə təkliflərinin generasiyası
     */
    protected function generateSuggestions(array $analysis): array
    {
        $suggestions = [];

        foreach ($analysis as $group => $data) {
            foreach ($data['details'] as $detail) {
                if ($detail['type'] === 'error' || $detail['type'] === 'warning') {
                    $suggestions[] = [
                        'group' => $group,
                        'priority' => $detail['type'] === 'error' ? 'high' : 'medium',
                        'message' => $detail['message']
                    ];
                }
            }
        }

        // Prioritetə görə sıralayırıq
        usort($suggestions, function($a, $b) {
            return $b['priority'] === 'high' ? 1 : -1;
        });

        return $suggestions;
    }

    /**
     * Detallı SEO analizi qaytarır
     */
    public function getDetailedAnalysis(SeoLink $seo): array
    {
        // Əsas analizi əldə edirik
        $baseAnalysis = $this->calculate($seo);

        // Detallı analiz üçün əlavə məlumatları toplayırıq
        return [
            'overview' => [
                'total_score' => $baseAnalysis['score'],
                'max_possible_score' => array_sum([
                    array_sum(self::MAX_SCORES['basic_meta']),
                    array_sum(self::MAX_SCORES['social_media']),
                    array_sum(self::MAX_SCORES['technical'])
                ]),
                'status' => $this->getScoreStatus($baseAnalysis['score']),
                'last_update' => $seo->updated_at
            ],
            'section_scores' => [
                'basic_meta' => [
                    'score' => $baseAnalysis['details']['basic_meta']['score'],
                    'max_score' => array_sum(self::MAX_SCORES['basic_meta']),
                    'percentage' => $this->calculatePercentage(
                        $baseAnalysis['details']['basic_meta']['score'],
                        array_sum(self::MAX_SCORES['basic_meta'])
                    )
                ],
                'social_media' => [
                    'score' => $baseAnalysis['details']['social']['score'],
                    'max_score' => array_sum(self::MAX_SCORES['social_media']),
                    'percentage' => $this->calculatePercentage(
                        $baseAnalysis['details']['social']['score'],
                        array_sum(self::MAX_SCORES['social_media'])
                    )
                ],
                'technical' => [
                    'score' => $baseAnalysis['details']['technical']['score'],
                    'max_score' => array_sum(self::MAX_SCORES['technical']),
                    'percentage' => $this->calculatePercentage(
                        $baseAnalysis['details']['technical']['score'],
                        array_sum(self::MAX_SCORES['technical'])
                    )
                ]
            ],
            'details' => $baseAnalysis['details'],
            'suggestions' => array_map(function($suggestion) {
                return [
                    ...$suggestion,
                    'impact' => $this->getImpactLevel($suggestion['priority'])
                ];
            }, $baseAnalysis['suggestions']),
            'improvements' => $this->generateImprovementPlan($baseAnalysis),
            'technical_details' => [
                'url_length' => strlen($seo->url),
                'total_meta_tags' => $this->countMetaTags($seo),
                'missing_required_tags' => $this->findMissingRequiredTags($seo),
                'duplicate_tags' => $this->findDuplicateTags($seo)
            ]
        ];
    }

    /**
     * Score-a görə status qaytarır
     */
    private function getScoreStatus(int $score): string
    {
        return match(true) {
            $score >= 90 => 'Əla',
            $score >= 70 => 'Yaxşı',
            $score >= 50 => 'Orta',
            default => 'Zəif'
        };
    }

    /**
     * Faiz hesablayır
     */
    private function calculatePercentage(int $score, int $maxScore): float
    {
        return round(($score / $maxScore) * 100, 1);
    }

    /**
     * Təsir səviyyəsini təyin edir
     */
    private function getImpactLevel(string $priority): array
    {
        return match($priority) {
            'high' => [
                'level' => 'Yüksək',
                'score_impact' => '10-20 bal',
                'importance' => 'Təcili düzəliş tələb edir'
            ],
            'medium' => [
                'level' => 'Orta',
                'score_impact' => '5-10 bal',
                'importance' => 'Yaxın zamanda düzəldilməli'
            ],
            default => [
                'level' => 'Aşağı',
                'score_impact' => '1-5 bal',
                'importance' => 'İmkan olduqda düzəldilə bilər'
            ]
        };
    }

    /**
     * Yaxşılaşdırma planı hazırlayır
     */
    private function generateImprovementPlan(array $analysis): array
    {
        $plan = [];

        foreach ($analysis['suggestions'] as $suggestion) {
            $plan[] = [
                'action' => $suggestion['message'],
                'priority' => $suggestion['priority'],
                'estimated_impact' => $this->getImpactLevel($suggestion['priority']),
                'group' => $suggestion['group'],
                'recommendation' => $this->getRecommendation($suggestion)
            ];
        }

        return $plan;
    }

    /**
     * Meta tagların sayını hesablayır
     */
    private function countMetaTags(SeoLink $seo): array
    {
        $basic = isset($seo->basic_meta) && is_array($seo->basic_meta) ? count(array_filter($seo->basic_meta)) : 0;
        $og = isset($seo->open_graph) && is_array($seo->open_graph) ? count(array_filter($seo->open_graph)) : 0;
        $twitter = isset($seo->twitter) && is_array($seo->twitter) ? count(array_filter($seo->twitter)) : 0;
        $technical = isset($seo->technical) && is_array($seo->technical) ? count(array_filter($seo->technical)) : 0;

        return [
            'basic' => $basic,
            'open_graph' => $og,
            'twitter' => $twitter,
            'technical' => $technical,
            'total' => $basic + $og + $twitter + $technical
        ];
    }

    /**
     * Məcburi amma əksik olan tagları tapır
     */
    private function findMissingRequiredTags(SeoLink $seo): array
    {
        $missing = [];

        $requiredTags = [
            'basic_meta' => ['title', 'description'],
            'open_graph' => ['og:title', 'og:description', 'og:image'],
            'twitter' => ['twitter:card', 'twitter:title', 'twitter:description']
        ];

        foreach ($requiredTags as $group => $tags) {
            $groupData = $seo->getAttribute($group);
            if (!is_array($groupData)) {
                continue;
            }

            foreach ($tags as $tag) {
                if (!isset($groupData[$tag]) || empty($groupData[$tag])) {
                    $missing[] = [
                        'group' => $group,
                        'tag' => $tag
                    ];
                }
            }
        }

        return $missing;
    }

    /**
     * Təkrarlanan meta tagları tapır
     */
    private function findDuplicateTags(SeoLink $seo): array
    {
        $duplicates = [];
        $values = [];

        // Basic meta tags
        foreach ($seo->basic_meta ?? [] as $key => $value) {
            // Array tipli dəyərləri keçirik
            if (is_array($value)) {
                continue;
            }
            $values[(string)$value][] = ['group' => 'basic_meta', 'key' => $key];
        }

        // OpenGraph tags
        foreach ($seo->open_graph ?? [] as $key => $value) {
            if (is_array($value)) {
                continue;
            }
            $values[(string)$value][] = ['group' => 'open_graph', 'key' => $key];
        }

        // Twitter tags
        foreach ($seo->twitter ?? [] as $key => $value) {
            if (is_array($value)) {
                continue;
            }
            $values[(string)$value][] = ['group' => 'twitter', 'key' => $key];
        }

        // Təkrarları tapırıq
        foreach ($values as $value => $occurrences) {
            if (count($occurrences) > 1) {
                $duplicates[] = [
                    'value' => $value,
                    'occurrences' => $occurrences
                ];
            }
        }

        return $duplicates;
    }

    /**
     * Təklif üçün tövsiyə hazırlayır
     */
    private function getRecommendation(array $suggestion): string
    {
        $recommendations = [
            'title' => [
                'high' => 'Title-ı 50-60 simvol arasında və açar sözləri əhatə edəcək şəkildə yenidən yazın',
                'medium' => 'Title uzunluğunu optimal həddə çatdırın'
            ],
            'description' => [
                'high' => 'Description-u 150-160 simvol arasında və əsas açar sözləri əhatə edəcək şəkildə yenidən yazın',
                'medium' => 'Description uzunluğunu optimal həddə çatdırın'
            ],
            'keywords' => [
                'high' => '3-7 arası əsas açar söz əlavə edin',
                'medium' => 'Açar sözləri optimallaşdırın'
            ],
            'open_graph' => [
                'high' => 'Əksik olan OpenGraph taglarını əlavə edin',
                'medium' => 'OpenGraph taglarını yoxlayın və təkmilləşdirin'
            ],
            'twitter' => [
                'high' => 'Əksik olan Twitter Card taglarını əlavə edin',
                'medium' => 'Twitter Card taglarını yoxlayın və təkmilləşdirin'
            ]
        ];

        // Mesajdan açar sözü tapmaq
        $key = null;
        $message = strtolower($suggestion['message']);

        foreach (array_keys($recommendations) as $recommendationKey) {
            if (Str::contains($message, $recommendationKey)) {
                $key = $recommendationKey;
                break;
            }
        }

        if (!$key) {
            return 'Göstərilən problemi həll edin';
        }

        return $recommendations[$key][$suggestion['priority']]
            ?? 'Göstərilən problemi həll edin';
    }
}
