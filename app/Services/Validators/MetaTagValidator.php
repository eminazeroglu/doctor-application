<?php

namespace App\Services\Validators;

class MetaTagValidator
{
    /**
     * Meta tag qrupları üçün maksimum uzunluqlar
     */
    protected const MAX_LENGTHS = [
        'basic_meta' => [
            'title' => 60,
            'description' => 160,
            'keywords' => 255
        ],
        'open_graph' => [
            'og:title' => 95,
            'og:description' => 200
        ],
        'twitter' => [
            'twitter:title' => 70,
            'twitter:description' => 200
        ]
    ];

    /**
     * Meta tag üçün əsas validasiya
     */
    public function validate(array $tagData): array
    {
        $errors = [];

        // Məcburi sahələrin yoxlanması
        if (empty($tagData['group'])) {
            $errors[] = 'Meta tag qrupu məcburidir';
        }

        if (empty($tagData['type'])) {
            $errors[] = 'Meta tag növü məcburidir';
        }

        if (!isset($tagData['content'])) {
            $errors[] = 'Meta tag məzmunu məcburidir';
        }

        // Qrup validasiyası
        if (!$this->isValidGroup($tagData['group'])) {
            $errors[] = 'Yanlış meta tag qrupu';
        }

        // Kontentin validasiyası
        $contentErrors = $this->validateContent(
            $tagData['group'],
            $tagData['type'],
            $tagData['content']
        );

        return array_merge($errors, $contentErrors);
    }

    /**
     * Bütün SEO məlumatlarının validasiyası
     */
    public function validateAll(array $seoData): array
    {
        $errors = [];

        // Basic meta məlumatlarının validasiyası
        if (isset($seoData['basic_meta'])) {
            $basicErrors = $this->validateBasicMeta($seoData['basic_meta']);
            if (!empty($basicErrors)) {
                $errors['basic_meta'] = $basicErrors;
            }
        }

        // OpenGraph məlumatlarının validasiyası
        if (isset($seoData['open_graph'])) {
            $ogErrors = $this->validateOpenGraph($seoData['open_graph']);
            if (!empty($ogErrors)) {
                $errors['open_graph'] = $ogErrors;
            }
        }

        // Twitter məlumatlarının validasiyası
        if (isset($seoData['twitter'])) {
            $twitterErrors = $this->validateTwitter($seoData['twitter']);
            if (!empty($twitterErrors)) {
                $errors['twitter'] = $twitterErrors;
            }
        }

        return $errors;
    }

    /**
     * Basic meta tagların validasiyası
     */
    protected function validateBasicMeta(array $meta): array
    {
        $errors = [];

        // Title validasiyası
        if (empty($meta['title'])) {
            $errors['title'] = 'Title məcburidir';
        } elseif (mb_strlen($meta['title']) > self::MAX_LENGTHS['basic_meta']['title']) {
            $errors['title'] = 'Title ' . self::MAX_LENGTHS['basic_meta']['title'] . ' simvoldan çox ola bilməz';
        }

        // Description validasiyası
        if (empty($meta['description'])) {
            $errors['description'] = 'Description məcburidir';
        } elseif (mb_strlen($meta['description']) > self::MAX_LENGTHS['basic_meta']['description']) {
            $errors['description'] = 'Description ' . self::MAX_LENGTHS['basic_meta']['description'] . ' simvoldan çox ola bilməz';
        }

        // Keywords validasiyası
        if (!empty($meta['keywords'])) {
            $keywords = explode(',', $meta['keywords']);
            if (count($keywords) > 10) {
                $errors['keywords'] = 'Maksimum 10 açar söz daxil edə bilərsiniz';
            }
        }

        // Title və Description əlaqəsi
        if (!empty($meta['title']) && !empty($meta['description'])) {
            if (!$this->titleDescriptionRelation($meta['title'], $meta['description'])) {
                $errors['relation'] = 'Description-da title-dan ən az bir açar söz olmalıdır';
            }
        }

        return $errors;
    }

    /**
     * OpenGraph tagların validasiyası
     */
    protected function validateOpenGraph(array $og): array
    {
        $errors = [];

        foreach ($og as $key => $value) {
            if (empty($value)) continue;

            // URL validasiyası
            if (in_array($key, ['og:url', 'og:image'])) {
                if (!filter_var($value, FILTER_VALIDATE_URL)) {
                    $errors[$key] = 'Düzgün URL formatı deyil';
                }
            }

            // Mətn uzunluğu validasiyası
            if (isset(self::MAX_LENGTHS['open_graph'][$key])) {
                $maxLength = self::MAX_LENGTHS['open_graph'][$key];
                if (mb_strlen($value) > $maxLength) {
                    $errors[$key] = "$key $maxLength simvoldan çox ola bilməz";
                }
            }
        }

        return $errors;
    }

    /**
     * Twitter Card tagların validasiyası
     */
    protected function validateTwitter(array $twitter): array
    {
        $errors = [];

        foreach ($twitter as $key => $value) {
            if (empty($value)) continue;

            // Card type validasiyası
            if ($key === 'twitter:card' && !in_array($value, ['summary', 'summary_large_image'])) {
                $errors[$key] = 'Yanlış card type';
            }

            // URL validasiyası
            if ($key === 'twitter:image') {
                if (!filter_var($value, FILTER_VALIDATE_URL)) {
                    $errors[$key] = 'Düzgün URL formatı deyil';
                }
            }

            // Mətn uzunluğu validasiyası
            if (isset(self::MAX_LENGTHS['twitter'][$key])) {
                $maxLength = self::MAX_LENGTHS['twitter'][$key];
                if (mb_strlen($value) > $maxLength) {
                    $errors[$key] = "$key $maxLength simvoldan çox ola bilməz";
                }
            }
        }

        return $errors;
    }

    /**
     * Meta tag qrupunun düzgünlüyünü yoxlayır
     */
    protected function isValidGroup(string $group): bool
    {
        return in_array($group, ['basic_meta', 'open_graph', 'twitter', 'technical']);
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
     * Kontentin növünə görə validasiyası
     */
    protected function validateContent(string $group, string $type, string $content): array
    {
        $errors = [];

        // Uzunluq validasiyası
        if (isset(self::MAX_LENGTHS[$group][$type])) {
            $maxLength = self::MAX_LENGTHS[$group][$type];
            if (mb_strlen($content) > $maxLength) {
                $errors[] = "$type $maxLength simvoldan çox ola bilməz";
            }
        }

        // URL validasiyası
        if (in_array($type, ['og:url', 'og:image', 'twitter:image', 'canonical'])) {
            if (!filter_var($content, FILTER_VALIDATE_URL)) {
                $errors[] = 'Düzgün URL formatı deyil';
            }
        }

        return $errors;
    }
}
