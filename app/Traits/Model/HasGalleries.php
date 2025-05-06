<?php

namespace App\Traits\Model;

use App\Services\App\Upload\ImageUploadService;
use Illuminate\Support\Str;

/**
 * HasGalleries Trait
 *
 * Bu trait modelə çoxlu şəkil yükləmə, silmə və idarə etmə funksionallığı əlavə edir.
 * JSON sahələrində çoxlu şəkil pathlarını saxlayır və onlar üçün metodlar və accessorlar təmin edir.
 *
 * Modeldə istifadəsi:
 *
 * 1. Trait-i modeldə use edin:
 *    use HasGalleries;
 *
 * 2. getGalleryFields() metodunu modelinizdə təmin edin:
 *    public function getGalleryFields(): array
 *    {
 *        return [
 *            'galleries' => [
 *                'path' => 'model/galleries',
 *                'thumbnail' => [300, 200],
 *                'medium' => [800, 600]
 *            ],
 *            'product_photos' => [
 *                'path' => 'model/products'
 *            ]
 *        ];
 *    }
 *
 * 3. Artıq bu metodları çağıra bilərsiniz:
 *    $model->uploadGalleries($request->file('photos'));
 *    $model->deleteGalleries('image.jpg');
 *    $urls = $model->getGalleriesUrls();
 *    $urls = $model->galleriesUrls; // accessor vasitəsilə
 */
trait HasGalleries
{
    /**
     * Trait-in boot metodu
     *
     * Model silindikdə qaleriya şəkillərini avtomatik silir
     */
    public static function bootHasGalleries(): void
    {
        // Model silindikdə, qalereya şəkillərini də silir
        static::deleting(function ($model) {
            if (method_exists($model, 'getGalleryFields')) {
                foreach (array_keys($model->getGalleryFields()) as $field) {
                    $model->clearGalleryPhotos($field);
                }
            }
        });
    }

    /**
     * Trait-in initialize metodu
     *
     * Bu metod model yaradıldıqda avtomatik çağırılır və qaleriya sahələrini hazırlayır
     */
    public function initializeHasGalleries(): void
    {
        if (method_exists($this, 'getGalleryFields')) {
            // Bütün qaleriya sahələri üçün array cast təyin edirik
            foreach (array_keys($this->getGalleryFields()) as $field) {
                $this->casts[$field] = 'array';

                // Accessor əlavə edirik - model->fieldUrls üçün
                $this->appendGalleryUrlsAccessor($field);
            }
        }
    }

    /**
     * Model üçün qaleriya sahələrinin konfiqurasiyasını təyin edir
     *
     * Hər model bu metodu özü təmin etməlidir
     *
     * @return array Qaleriya sahələrinin konfiqurasiyası
     *
     * Misal:
     * [
     *   'galleries' => [
     *     'path' => 'model_folder/galleries',  // Şəkillərin saxlanacağı qovluq
     *     'thumbnail' => [300, 300],           // Optional - thumbnail ölçüsü [width, height]
     *     'medium' => [800, 600],              // Optional - medium ölçü [width, height]
     *     'large' => [1200, 900],              // Optional - large ölçü [width, height]
     *     'watermark' => true,                 // Optional - watermark əlavə etmək
     *     'default_image' => 'default.webp'    // Optional - default şəkil adı
     *   ],
     *   'product_photos' => [
     *     'path' => 'model_folder/products'    // Minimum konfiqurasiya - yalnız path
     *   ]
     * ]
     */
    abstract public function getGalleryFields(): array;

    /**
     * Qaleriya üçün URL accessor-u əlavə edir
     *
     * Bu metod qaleriya sahəsi üçün '{field}Urls' adlı accessor yaradır
     * Məsələn: galleries sahəsi üçün galleriesUrls accessor-u
     *
     * @param string $field Qaleriya sahəsinin adı
     */
    protected function appendGalleryUrlsAccessor(string $field): void
    {
        $accessorName = lcfirst(Str::studly($field)) . 'Urls';

        // Appends massivində bu accessor varsa əlavə edirik
        if (property_exists($this, 'appends') && !in_array($accessorName, $this->appends)) {
            $this->appends[] = $accessorName;
        }
    }

    /**
     * Magic metod - dinamik olaraq qaleriya metodlarını çağırır
     *
     * Bu metod ilə aşağıdakı metodları çağıra bilərsiniz:
     * - uploadGalleries(), uploadProductPhotos(), və s.
     * - deleteGalleries(), deleteProductPhotos(), və s.
     * - clearGalleries(), clearProductPhotos(), və s.
     * - reorderGalleries(), reorderProductPhotos(), və s.
     * - getGalleriesUrls(), getProductPhotosUrls(), və s.
     * - getAllGalleriesUrls(), getAllProductPhotosUrls(), və s.
     * - getGalleries(), getProductPhotos(), və s.
     *
     * @param string $method Çağırılan metodun adı
     * @param array $parameters Metoda ötürülən parametrlər
     * @return mixed Metodun nəticəsi
     */
    public function __call($method, $parameters)
    {
        // Bütün qaleriya sahələri
        $galleryFields = method_exists($this, 'getGalleryFields') ? $this->getGalleryFields() : [];
        $fieldNames = array_keys($galleryFields);

        // Metod adını analiz edirik
        if (preg_match('/^(upload|delete|clear|reorder|get|getAll)(.*?)(Urls)?$/', $method, $matches)) {
            $action = $matches[1];     // upload, delete, clear və s.
            $fieldStudly = $matches[2]; // StudlyCase format: Galleries, ProductPhotos
            $hasUrls = !empty($matches[3]); // URLs sözü varsa

            // Field adının snake_case versiyasını əldə edirik
            $fieldSnake = Str::snake($fieldStudly);

            // Əgər metod adındakı sahə mövcud deyilsə, bəlkə birinci hərfi kiçikdir
            if (!in_array($fieldSnake, $fieldNames)) {
                $fieldSnake = Str::snake(lcfirst($fieldStudly));
            }

            // Uyğun sahə adını tapmağa çalışırıq
            foreach ($fieldNames as $name) {
                // field_name ilə field_names və ya fieldName ilə fieldNames kimi oxşar adları tutur
                if ($name === $fieldSnake || Str::startsWith($name, $fieldSnake) || Str::startsWith($fieldSnake, $name)) {
                    $field = $name;

                    // Uyğun metodları çağırırıq
                    switch ($action) {
                        case 'upload':
                            return $this->uploadGalleryPhotos($parameters[0] ?? [], $field, $parameters[1] ?? null);
                        case 'delete':
                            return $this->deleteGalleryPhotos($parameters[0] ?? [], $field, $parameters[1] ?? null);
                        case 'clear':
                            return $this->clearGalleryPhotos($field, $parameters[0] ?? null);
                        case 'reorder':
                            return $this->reorderGalleryPhotos($parameters[0] ?? [], $field);
                        case 'get':
                            if ($hasUrls) {
                                return $this->getGalleryUrls($field, $parameters[0] ?? 'original', $parameters[1] ?? null);
                            } else {
                                return $this->getGalleryPhotos($field);
                            }
                        case 'getAll':
                            if ($hasUrls) {
                                return $this->getAllGalleryUrls($field, $parameters[0] ?? null);
                            }
                            break;
                    }

                    break;
                }
            }
        }

        // Qaleriya accessor-ları - fieldUrls kimi
        if (method_exists($this, 'getGalleryFields')) {
            foreach (array_keys($this->getGalleryFields()) as $field) {
                $accessorName = lcfirst(Str::studly($field)) . 'Urls';
                if ($method === $accessorName) {
                    return $this->getGalleryUrls($field);
                }
            }
        }

        // Əgər mövcud metod deyilsə, parent::__call çağırırıq
        return parent::__call($method, $parameters);
    }

    /**
     * Qaleriya şəkillərini yükləyir
     *
     * Bu metod şəkilləri serverə yükləyir və qaleriya sahəsində saxlayır.
     * Thumbnail, medium və large ölçülər konfiqurasiyaya əsasən yaradılır.
     *
     * @param array $images Yüklənəcək şəkillər (request->file() və ya base64 və ya URL)
     * @param string $field Qaleriya sahəsinin adı
     * @param string|null $customPath Xüsusi yüklənmə yolu (optional)
     * @return array Yüklənmiş şəkillərin adları
     *
     * İstifadə nümunəsi:
     * $model->uploadGalleryPhotos($request->file('photos'), 'galleries');
     */
    public function uploadGalleryPhotos(array $images, string $field, string $customPath = null): array
    {
        // Qaleriya konfiqurasiyasını əldə edirik
        $config = $this->getGalleryFields()[$field] ?? [];
        $basePath = $config['path'] ?? $this->getTable() . '/' . $field;
        $uploadPath = $customPath ?? $basePath;

        // Mövcud şəkilləri götürürük
        $currentPhotos = $this->{$field} ?? [];

        // ImageUploadService yaradırıq
        $imageService = new ImageUploadService();

        // Yeni şəkil yolları
        $newPhotos = [];

        // Hər bir şəkili yükləyirik
        foreach ($images as $image) {
            if (empty($image)) {
                continue;
            }

            // Şəkil yükləmə servisini konfiqurasiya edirik
            $imageService->setFile($image)
                ->setPath($uploadPath)
                ->setName(md5(Str::random(10) . time()));

            // Base64 və URL yoxlaması
            if (is_string($image)) {
                if (str_starts_with($image, 'data:image')) {
                    $imageService->setBase64(true);
                } elseif (filter_var($image, FILTER_VALIDATE_URL)) {
                    $imageService->setUrl(true);
                }
            }

            // Opsional konfiqurasiyalar
            if (isset($config['thumbnail']) && is_array($config['thumbnail']) && count($config['thumbnail']) >= 2) {
                $imageService->setThumbnail($config['thumbnail'][0], $config['thumbnail'][1]);
            }

            if (isset($config['medium']) && is_array($config['medium']) && count($config['medium']) >= 2) {
                $imageService->setMedium($config['medium'][0], $config['medium'][1]);
            }

            if (isset($config['large']) && is_array($config['large']) && count($config['large']) >= 2) {
                $imageService->setLarge($config['large'][0], $config['large'][1]);
            }

            if (isset($config['watermark']) && $config['watermark'] === true) {
                $imageService->setWatermark(true);
            }

            // Şəkili yükləyirik
            $photoName = $imageService->upload();

            if ($photoName) {
                $newPhotos[] = $photoName;
            }
        }

        // Yeni şəkilləri əlavə edirik
        if (!empty($newPhotos)) {
            $allPhotos = array_merge($currentPhotos, $newPhotos);
            $this->update([$field => $allPhotos]);
        }

        return $newPhotos;
    }

    /**
     * Qaleriya şəkillərini silir
     *
     * Bu metod serverden seçilmiş şəkilləri silir və qaleriya sahəsindən çıxarır.
     *
     * @param array|string $photos Silinəcək şəkillər (tək ad və ya adlar massivi)
     * @param string $field Qaleriya sahəsinin adı
     * @param string|null $customPath Xüsusi yol (optional)
     * @return bool Əməliyyat nəticəsi
     *
     * İstifadə nümunəsi:
     * $model->deleteGalleryPhotos('image.jpg', 'galleries');
     * $model->deleteGalleryPhotos(['image1.jpg', 'image2.jpg'], 'galleries');
     */
    public function deleteGalleryPhotos(array|string $photos, string $field, string $customPath = null): bool
    {
        if (empty($photos)) {
            return false;
        }

        // Tək element massivə çevrilir
        if (!is_array($photos)) {
            $photos = [$photos];
        }

        // Qaleriya konfiqurasiyasını əldə edirik
        $config = $this->getGalleryFields()[$field] ?? [];
        $basePath = $config['path'] ?? $this->getTable() . '/' . $field;
        $deletePath = $customPath ?? $basePath;

        // Mövcud şəkilləri götürürük
        $currentPhotos = $this->{$field} ?? [];

        if (empty($currentPhotos)) {
            return false;
        }

        // ImageUploadService yaradırıq
        $imageService = new ImageUploadService();

        // Silinməyəcək şəkilləri saxlayırıq
        $remainingPhotos = [];

        foreach ($currentPhotos as $photo) {
            if (in_array($photo, $photos)) {
                // Fiziki olaraq serverden silirik
                $imageService->delete($deletePath, $photo);
            } else {
                // Silinməyəcək şəkillər
                $remainingPhotos[] = $photo;
            }
        }

        // Qalan şəkilləri update edirik
        $this->update([$field => $remainingPhotos]);

        return true;
    }

    /**
     * Bütün qaleriya şəkillərini silir
     *
     * Bu metod bütün qaleriya şəkillərini serverden və model sahəsindən silir.
     *
     * @param string $field Qaleriya sahəsinin adı
     * @param string|null $customPath Xüsusi yol (optional)
     * @return bool Əməliyyat nəticəsi
     *
     * İstifadə nümunəsi:
     * $model->clearGalleryPhotos('galleries');
     */
    public function clearGalleryPhotos(string $field, string $customPath = null): bool
    {
        // Qaleriya konfiqurasiyasını əldə edirik
        $config = $this->getGalleryFields()[$field] ?? [];
        $basePath = $config['path'] ?? $this->getTable() . '/' . $field;
        $deletePath = $customPath ?? $basePath;

        // Mövcud şəkilləri götürürük
        $currentPhotos = $this->{$field} ?? [];

        if (empty($currentPhotos)) {
            return true;
        }

        // ImageUploadService yaradırıq
        $imageService = new ImageUploadService();

        // Bütün şəkilləri silir
        foreach ($currentPhotos as $photo) {
            $imageService->delete($deletePath, $photo);
        }

        // Boş massiv ilə update edirik
        $this->update([$field => []]);

        return true;
    }

    /**
     * Qaleriya şəkillərini yenidən sıralayır
     *
     * Bu metod qaleriya şəkillərinin sırasını dəyişir.
     *
     * @param array $newOrder Yeni şəkil sırası (şəkil adları massivi)
     * @param string $field Qaleriya sahəsinin adı
     * @return bool Əməliyyat nəticəsi
     *
     * İstifadə nümunəsi:
     * $model->reorderGalleryPhotos(['image3.jpg', 'image1.jpg', 'image2.jpg'], 'galleries');
     */
    public function reorderGalleryPhotos(array $newOrder, string $field): bool
    {
        // Mövcud şəkilləri götürürük
        $currentPhotos = $this->{$field} ?? [];

        // Əgər ölçülər eyni deyilsə və ya yeni massivdə əksilmə varsa
        if (count($newOrder) !== count($currentPhotos) ||
            count(array_diff($newOrder, $currentPhotos)) > 0) {
            return false;
        }

        // Yeni sıra ilə update edirik
        $this->update([$field => $newOrder]);

        return true;
    }

    /**
     * Qaleriya şəkillərini qaytarır
     *
     * Bu metod model sahəsində saxlanılan şəkil adlarını qaytarır.
     *
     * @param string $field Qaleriya sahəsinin adı
     * @return array Şəkil adları
     *
     * İstifadə nümunəsi:
     * $photos = $model->getGalleryPhotos('galleries');
     */
    public function getGalleryPhotos(string $field): array
    {
        return $this->{$field} ?? [];
    }

    /**
     * Qaleriya şəkillərinin URL-lərini qaytarır
     *
     * Bu metod qaleriya şəkillərinin tam URL-lərini istənilən ölçüdə qaytarır.
     *
     * @param string $field Qaleriya sahəsinin adı
     * @param string $size Şəkil ölçüsü (original, thumbnail, medium, large)
     * @param string|null $customPath Xüsusi yol (optional)
     * @return array URL-lər
     *
     * İstifadə nümunəsi:
     * $urls = $model->getGalleryUrls('galleries');
     * $thumbnails = $model->getGalleryUrls('galleries', 'thumbnail');
     */
    public function getGalleryUrls(string $field, string $size = 'original', string $customPath = null): array
    {
        // Qaleriya konfiqurasiyasını əldə edirik
        $config = $this->getGalleryFields()[$field] ?? [];
        $basePath = $config['path'] ?? $this->getTable() . '/' . $field;
        $urlPath = $customPath ?? $basePath;
        $defaultImage = $config['default_image'] ?? 'default_gallery.webp';

        // Mövcud şəkilləri götürürük
        $photos = $this->{$field} ?? [];

        if (empty($photos)) {
            return [];
        }

        // ImageUploadService yaradırıq
        $imageService = new ImageUploadService();
        $urls = [];

        // Hər bir şəkil üçün URL əldə edirik
        foreach ($photos as $photo) {
            $photoUrls = $imageService->getPhoto(
                $urlPath,
                $photo,
                $defaultImage
            );

            // İstənilən ölçüdə URL əlavə edirik
            if (isset($photoUrls[$size])) {
                $urls[] = $photoUrls[$size];
            } else {
                $urls[] = $photoUrls['original'] ?? null;
            }
        }

        // Boş URL-ləri çıxarırıq
        return array_filter($urls);
    }

    /**
     * Qaleriya şəkillərinin bütün URL-lərini bütün ölçülərdə qaytarır
     *
     * Bu metod qaleriya şəkillərinin bütün mümkün ölçülərdə URL-lərini qaytarır.
     *
     * @param string $field Qaleriya sahəsinin adı
     * @param string|null $customPath Xüsusi yol (optional)
     * @return array URL-lər ölçülərə görə
     *
     * İstifadə nümunəsi:
     * $allUrls = $model->getAllGalleryUrls('galleries');
     *
     * Nəticə massivi:
     * [
     *   0 => [
     *     'original' => 'https://...image1.jpg',
     *     'thumbnail' => 'https://...image1_thumb.jpg',
     *     'medium' => 'https://...image1_medium.jpg'
     *   ],
     *   1 => [
     *     'original' => 'https://...image2.jpg',
     *     'thumbnail' => 'https://...image2_thumb.jpg',
     *     'medium' => 'https://...image2_medium.jpg'
     *   ]
     * ]
     */
    public function getAllGalleryUrls(string $field, string $customPath = null): array
    {
        // Qaleriya konfiqurasiyasını əldə edirik
        $config = $this->getGalleryFields()[$field] ?? [];
        $basePath = $config['path'] ?? $this->getTable() . '/' . $field;
        $urlPath = $customPath ?? $basePath;
        $defaultImage = $config['default_image'] ?? 'default_gallery.webp';

        // Mövcud şəkilləri götürürük
        $photos = $this->{$field} ?? [];

        if (empty($photos)) {
            return [];
        }

        // ImageUploadService yaradırıq
        $imageService = new ImageUploadService();

        // Hər bir şəkil üçün bütün ölçülərdə URL əldə edirik
        return array_map(function ($photo) use ($imageService, $urlPath, $defaultImage) {
            return $imageService->getPhoto(
                $urlPath,
                $photo,
                $defaultImage
            );
        }, $photos);
    }
}
