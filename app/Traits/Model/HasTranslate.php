<?php

namespace App\Traits\Model;

use App\Helpers\Helper;
use App\Services\App\Upload\ImageUploadService;
use Illuminate\Database\Eloquent\Builder;


trait HasTranslate
{
    public static function bootHasTranslate(): void
    {
        static::addGlobalScope('appendTranslations', function (Builder $builder) {
            $builder->macro('getModel', function () use ($builder) {
                return $builder->getModel()->append($builder->getModel()->getTranslatableAttributes());
            });
        });

        // Translates-də şəkil sahələrini yükləmək üçün əlavə edirəm
        static::saving(function ($model) {
            if (method_exists($model, 'getTranslatableImageFields') && count($model->getTranslatableImageFields()) > 0 &&
                isset($model->attributes['translates']) && $model->attributes['translates']) {

                $translates = json_decode($model->attributes['translates'], true);
                $imageFields = $model->getTranslatableImageFields();
                $modifiedTranslates = [];

                // Hər bir dil üçün şəkilləri yoxlayırıq
                foreach ($translates as $lang => $values) {
                    $modifiedTranslates[$lang] = $values;

                    foreach ($imageFields as $field => $config) {
                        // Şəkil sahələri üçün yoxlayırıq
                        if (!empty($values[$field]) &&
                            (is_string($values[$field]) && str_starts_with($values[$field], 'data:image'))) {

                            // Şəkili yükləyirik
                            $imageService = new ImageUploadService();
                            $path = $config['path'] ?? $model->getTable();
                            $isBase64 = $config['base64'] ?? true;

                            $uploadedImage = $imageService
                                ->setPath($path)
                                ->setBase64($isBase64)
                                ->setFile($values[$field])
                                ->upload();

                            if ($uploadedImage) {
                                // Dil məlumatlarını yeniləyirik
                                $modifiedTranslates[$lang][$field] = $uploadedImage;

                                // URL sahəsi əlavə edirik
                                $urlField = $field . '_url';
                                $modifiedTranslates[$lang][$urlField] = $imageService->getPhoto(
                                    $path,
                                    $uploadedImage,
                                    $config['default_image'] ?? 'default_photo.webp'
                                )['original'];
                            }
                        }
                    }
                }

                // Yeni tərcümə məlumatlarını yeniləyirik
                $model->attributes['translates'] = json_encode($modifiedTranslates);
            }
        });
    }

    public function initializeHasTranslate(): void
    {
        $this->casts['translates'] = 'json';
    }

    /**
     * Get the image field name for the model.
     *
     * @return array
     * [
     *      'image' => [
     *          'path' => 'products',
     *          'base64' => true|false,
     *      ]
     * ];
     */
    public function getTranslatableImageFields(): array
    {
        return [];
    }


    abstract public function getTranslatableAttributes(): array;

    public function setAttribute($key, $value)
    {
        if ($key === 'translates' && is_array($value)) {
            $this->attributes['translates'] = json_encode($value);
        } elseif (in_array($key, $this->getTranslatableAttributes())) {
            $translates = $this->translates ?? [];
            $translates[Helper::language()][$key] = $value;
            $this->attributes['translates'] = json_encode($translates);
        } else {
            return parent::setAttribute($key, $value);
        }

        return $this;
    }

    public function getAttribute($key)
    {
        $value = parent::getAttribute($key);

        if ($value === null && in_array($key, $this->getTranslatableAttributes())) {
            $translates = $this->translates ?? [];
            return $translates[Helper::language()][$key] ?? null;
        }

        return $value;
    }

    public function getTranslation($key, $locale = null)
    {
        $locale = $locale ?: Helper::language();
        $translates = $this->translates ?? [];
        return $translates[$locale][$key] ?? null;
    }

    public function setTranslation($key, $value, $locale = null): static
    {
        $locale = $locale ?: Helper::language();
        $translates = $this->translates ?? [];
        $translates[$locale][$key] = $value;
        $this->attributes['translates'] = json_encode($translates);
        return $this;
    }

    public function toArray(): array
    {
        $attributes = parent::toArray();

        // Tərcümə atributlarını yalnız əsas model üçün əlavə edirik
        foreach ($this->getTranslatableAttributes() as $attribute) {
            try {
                $attributes[$attribute] = $this->getAttribute($attribute);
            } catch (\Exception $e) {
                $attributes[$attribute] = null; // Xəta olarsa null qaytar
            }
        }

        return $attributes;
    }
}
