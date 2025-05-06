<?php

namespace App\Traits\Model;

use App\Services\App\Upload\ImageUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

trait HasImage
{
    protected bool $useBase64 = false;
    protected bool $useUrl = false;

    /**
     * Base64 flag-ni təyin edir
     */
    public function setUseBase64(bool $value): static
    {
        $this->useBase64 = $value;
        return $this;
    }

    /**
     * URL flag-ni təyin edir
     */
    public function setUseUrl(bool $value): static
    {
        $this->useUrl = $value;
        return $this;
    }

    /**
     * Trait-i boot edir
     */
    public static function bootHasImage(): void
    {
        // Model yadda saxlanılanda şəkilləri upload edir
        static::saving(function ($model) {
            $model->uploadImages();
        });

        // Model silindikdə şəkilləri silir
        static::deleting(function ($model) {
            $model->deleteImages();
        });
    }

    /**
     * Model-də olan şəkil sahələrini təyin edir
     *
     * @return array
     * [
     *      'image' => [
     *          'path' => 'products',
     *          'multiple' => true|false,
     *          'thumbnail' => [100, 100] | true (Settings-dən istifadə et),
     *          'medium' => [300, 300] | true (Settings-dən istifadə et),
     *          'large' => [600, 600] | true (Settings-dən istifadə et),
     *          'base64' => true|false,
     *          'default_image' => 'default.webp',
     *          'watermark' => true|false
     *      ]
     * ];
     */
    abstract public function getImageFields(): array;

    /**
     * Şəkilləri upload edir
     */
    public function uploadImages(): void
    {
        $imageService = new ImageUploadService();

        foreach ($this->getImageFields() as $field => $config) {
            if ($this->isImageFieldUpdated($field)) {
                $file = $this->getImageFile($field);
                $base64 = $this->useBase64;

                if (@$config['base64']) {
                    $base64 = $config['base64'];
                }

                if ($file) {
                    $imageService->setFile($file)
                        ->setPath($config['path'])
                        ->setName($this->generateImageName())
                        ->setRemoveFile($this->getOriginal($field))
                        ->setBase64($base64)
                        ->setUrl($this->useUrl);

                    // Watermark parametrini yoxlayırıq və tətbiq edirik
                    if (isset($config['watermark']) && $config['watermark'] === true) {
                        $imageService->setApplyWatermark(true);
                    }

                    // Ölçüləri konfiqurasiya edirik
                    $this->configureImageSizes($imageService, $config);

                    $fileName = $imageService->upload();
                    if ($fileName) {
                        $this->attributes[$field] = $fileName;
                    }
                }
            }
        }
    }

    /**
     * Şəkilləri silir
     */
    public function deleteImages(): void
    {
        $imageService = new ImageUploadService();

        foreach ($this->getImageFields() as $field => $config) {
            if (!empty($config['multiple'])) {
                // Multiple şəkillər üçün
                if (isset($this->attributes[$field]) && is_array($this->attributes[$field])) {
                    foreach ($this->attributes[$field] as $image) {
                        $imageService->delete($config['path'], $image);
                    }
                }
            } else {
                // Tək şəkil üçün
                if (isset($this->attributes[$field]) && $this->attributes[$field]) {
                    $imageService->delete($config['path'], $this->attributes[$field]);
                }
            }
        }
    }

    /**
     * Şəkil ölçülərini konfiqurasiya edir
     */
    protected function configureImageSizes(ImageUploadService $imageService, array $config): void
    {
        // Upload settings-i əldə edirik
        $uploadSettings = setting('upload.image_sizes');

        // Thumbnail üçün
        if (isset($config['thumbnail'])) {
            // Əgər thumbnail array olaraq verilibsə (manual ölçülər)
            if (is_array($config['thumbnail'])) {
                $imageService->setThumbnail(
                    $config['thumbnail'][0],
                    $config['thumbnail'][1]
                );
            }
            // Əgər thumbnail true olaraq verilibsə (settings-dən ölçülər)
            elseif ($config['thumbnail'] === true && $uploadSettings) {
                $imageService->setThumbnail(
                    $uploadSettings['thumbnail']['width'],
                    $uploadSettings['thumbnail']['height']
                );
            }
        }

        // Medium üçün
        if (isset($config['medium'])) {
            // Əgər medium array olaraq verilibsə (manual ölçülər)
            if (is_array($config['medium'])) {
                $imageService->setMedium(
                    $config['medium'][0],
                    $config['medium'][1]
                );
            }
            // Əgər medium true olaraq verilibsə (settings-dən ölçülər)
            elseif ($config['medium'] === true && $uploadSettings) {
                $imageService->setMedium(
                    $uploadSettings['medium']['width'],
                    $uploadSettings['medium']['height']
                );
            }
        }

        // Large üçün
        if (isset($config['large'])) {
            // Əgər large array olaraq verilibsə (manual ölçülər)
            if (is_array($config['large'])) {
                $imageService->setLarge(
                    $config['large'][0],
                    $config['large'][1]
                );
            }
            // Əgər large true olaraq verilibsə (settings-dən ölçülər)
            elseif ($config['large'] === true && $uploadSettings) {
                $imageService->setLarge(
                    $uploadSettings['large']['width'],
                    $uploadSettings['large']['height']
                );
            }
        }
    }

    /**
     * Multiple şəkil fayllarını əldə edir
     */
    protected function getMultipleImageFiles(string $field): array
    {
        if ($this->useBase64) {
            $files = request()->input($field) ?? [];
            return is_array($files) ? $files : [$files];
        }
        elseif ($this->useUrl) {
            $urls = request()->input($field) ?? [];
            return is_array($urls) ? $urls : [$urls];
        }
        else {
            return request()->file($field) ?? [];
        }
    }

    /**
     * Tək şəkil faylını əldə edir
     */
    protected function getImageFile(string $field): UploadedFile|string|null
    {
        if ($this->useBase64) {
            return request()->input($field);
        }
        elseif ($this->useUrl) {
            return request()->input($field);
        }
        else {
            return request()->file($field) ??
                $this->attributes[$field] ??
                null;
        }
    }

    /**
     * Şəkil sahəsinin yenilənib-yenilənmədiyini yoxlayır
     */
    protected function isImageFieldUpdated(string $field): bool
    {
        if ($this->useBase64) {
            return $this->isDirty($field) ||
                (request()->has($field) &&
                    (is_array(request()->input($field)) ||
                        Str::startsWith(request()->input($field), 'data:image')));
        }
        elseif ($this->useUrl) {
            return $this->isDirty($field) ||
                (request()->has($field) &&
                    (is_array(request()->input($field)) ||
                        filter_var(request()->input($field), FILTER_VALIDATE_URL)));
        }
        else {
            return $this->isDirty($field) || request()->hasFile($field);
        }
    }

    /**
     * Unikal şəkil adı generasiya edir
     */
    protected function generateImageName(): string
    {
        return md5(Str::random(10) . time());
    }

    /**
     * Şəklin URL-ni əldə edir
     *
     * @param string $field şəkil sahəsinin adı (məs: logo_path)
     * @param string $size şəkil ölçüsü (original, thumbnail, medium, large)
     * @return string|array|null Multiple şəkillər üçün array, tək şəkil üçün string
     */
    public function getImageUrl(string $field, string $size = 'original'): string|array|null
    {
        if (!isset($this->attributes[$field])) {
            return url('uploads/photos/setting/default_photo.webp');
        }

        $imageService = new ImageUploadService();
        $config = $this->getImageFields()[$field];

        // Multiple şəkillər üçün
        if (!empty($config['multiple']) && is_array($this->attributes[$field])) {
            $urls = [];
            foreach ($this->attributes[$field] as $image) {
                $photoUrls = $imageService->getPhoto(
                    $config['path'],
                    $image,
                    $config['default_image'] ?? 'default_photo.webp'
                );
                $urls[] = $photoUrls[$size] ?? $photoUrls['original'] ?? null;
            }
            return $urls;
        }

        // Tək şəkil üçün
        $photoUrls = $imageService->getPhoto(
            $config['path'],
            $this->attributes[$field],
            $config['default_image'] ?? 'default_photo.webp'
        );

        return $photoUrls[$size] ?? $photoUrls['original'] ?? null;
    }

    /**
     * Şəklin bütün URL-lərini əldə edir
     */
    public function getAllImageUrls(string $field): ?array
    {
        if (!isset($this->attributes[$field])) {
            return null;
        }

        $imageService = new ImageUploadService();
        $config = $this->getImageFields()[$field];

        // Multiple şəkillər üçün
        if (!empty($config['multiple']) && is_array($this->attributes[$field])) {
            $allUrls = [];
            foreach ($this->attributes[$field] as $image) {
                $allUrls[] = $imageService->getPhoto(
                    $config['path'],
                    $image,
                    $config['default_image'] ?? 'default_photo.webp'
                );
            }
            return $allUrls;
        }

        // Tək şəkil üçün
        return $imageService->getPhoto(
            $config['path'],
            $this->attributes[$field],
            $config['default_image'] ?? 'default_photo.webp'
        );
    }
}
