<?php

namespace App\Traits\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait HasSlug
{
    /**
     * Boot the trait.
     */
    public static function bootHasSlug(): void
    {
        // Creating zamanı slug yaratmaq
        static::creating(function (Model $model) {
            if (in_array($model->getSlugFieldName(), $model->getFillable())) {
                $model->generateUniqueSlug();
            }
        });

        // Update zamanı slug yeniləmək
        static::updating(function (Model $model) {
            if (in_array($model->getSlugFieldName(), $model->getFillable()) &&
                ($model->isDirty($model->getSlugSourceColumn()) || $model->isDirty('translates'))) {
                $model->generateUniqueSlug();
            }
        });
    }

    /**
     * Generate unique slug for the model.
     */
    protected function generateUniqueSlug(): void
    {
        if ($this->hasTranslations()) {
            $this->generateSingleSlug();
            $this->generateTranslatedSlugs();
        } else {
            $this->generateSingleSlug();
        }
    }

    /**
     * Check if model has translations.
     */
    protected function hasTranslations(): bool
    {
        return isset($this->attributes['translates']) &&
            is_array($this->getTranslations()) &&
            method_exists($this, 'getTranslatableAttributes') &&
            in_array($this->getSlugSourceColumn(), $this->getTranslatableAttributes());
    }

    /**
     * Get translations array.
     */
    protected function getTranslations(): ?array
    {
        return json_decode($this->attributes['translates'] ?? '{}', true);
    }

    /**
     * Generate slugs for translated content.
     */
    protected function generateTranslatedSlugs(): void
    {
        $translates = $this->getTranslations();
        $defaultLocale = config('app.fallback_locale', 'az');

        foreach ($translates as $locale => $translations) {
            $source = $translations[$this->getSlugSourceColumn()] ?? '';
            if (!empty($source)) {
                $slug = $this->createUniqueSlug($source);
                $translates[$locale][$this->getSlugFieldName()] = $slug;

                // Default dil üçün əsas slug sahəsini də yeniləyirik
                if ($locale === $defaultLocale) {
                    $this->{$this->getSlugFieldName()} = $slug;
                }
            }
        }

        $this->attributes['translates'] = json_encode($translates);
    }

    /**
     * Generate single slug for non-translated content.
     */
    protected function generateSingleSlug(): void
    {
        $source = $this->{$this->getSlugSourceColumn()};
        if (!empty($source)) {
            $this->{$this->getSlugFieldName()} = $this->createUniqueSlug($source);
        }
    }

    /**
     * Create a unique slug.
     */
    protected function createUniqueSlug(string $source): string
    {
        $slug = Str::slug($source);
        $originalSlug = $slug;
        $count = 2;

        while ($this->slugExists($slug)) {
            $slug = $originalSlug . '-' . $count++;
        }

        return $slug;
    }

    /**
     * Check if the slug already exists.
     */
    protected function slugExists(string $slug): bool
    {
        $key = $this->getKey();
        $query = static::where($this->getSlugFieldName(), $slug);

        if ($key) {
            $query->where($this->getKeyName(), '!=', $key);
        }

        return $query->exists();
    }

    /**
     * Get the source column for the slug.
     */
    protected function getSlugSourceColumn(): string
    {
        return $this->slugSource ?? 'name';
    }

    /**
     * Get the database column name for the slug.
     */
    protected function getSlugFieldName(): string
    {
        return $this->slugField ?? 'slug';
    }

    /**
     * Get slug for specific language.
     */
    public function getSlugByLang(string $locale): ?string
    {
        if ($this->hasTranslations()) {
            $translates = $this->getTranslations();
            return $translates[$locale][$this->getSlugFieldName()] ?? null;
        }

        return $this->{$this->getSlugFieldName()};
    }

    /**
     * Get all slugs for all languages.
     */
    public function getAllSlugs(): array
    {
        if ($this->hasTranslations()) {
            $translates = $this->getTranslations();

            return array_map(function ($translations) {
                return $translations[$this->getSlugFieldName()] ?? null;
            }, $translates);
        }

        return [$this->getSlugFieldName() => $this->{$this->getSlugFieldName()}];
    }
}
