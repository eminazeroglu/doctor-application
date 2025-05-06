<?php

namespace App\Services\App\Upload;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Encoders\GifEncoder;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Encoders\PngEncoder;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;

class ImageUploadService
{
    // Əsas konfiqurasiya parametrləri
    private ImageManager $manager;
    private string $driver;
    private string $mainFolder = '/uploads/photos';
    private array $allowedFormats = [];
    private array $rasterFormats = [];
    private array $vectorFormats = [];
    private int $quality;
    private bool $isSettingsMode = false;
    private bool $isInitialized = false;
    // Şəkil parametrləri
    private ?string $name = null;
    private ?string $path = null;
    private bool $base64 = false;
    private bool $url = false;
    private ?array $size = null;
    private bool $watermarkEnabled;
    private string $watermarkPosition;
    private int $watermarkOpacity;
    private array $watermarkSettings = [];
    private array $watermark = [];
    private ?array $thumbnail = null;
    private ?array $medium = null;
    private ?array $large = null;
    private $file;
    private ?string $remove = null;
    private ?int $rotate = null;
    private string $defaultImage;
    private bool $applyWatermark = false;

    public function __construct()
    {
        // Əsas konfiqurasiyaları qururuq
        $this->driver = config('filesystems.default', 'local');
        $this->manager = new ImageManager(new GdDriver());

        // Settings-dən şəkil tənzimləmələrini əldə edirik
        $this->setDefaultValues();

        // Upload qovluğunu yaradırıq
        $this->createUploadPath();
    }

    private function setDefaultValues(): void
    {
        $this->allowedFormats = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
        $this->quality = 85;
        $this->defaultImage = 'default_photo.webp';

        // Raster və vector formatları
        $this->rasterFormats = array_filter($this->allowedFormats, fn($format) => $format !== 'svg');
        $this->vectorFormats = in_array('svg', $this->allowedFormats) ? ['svg'] : [];
    }

    private function ensureInitialized(): void
    {
        if (!$this->isInitialized && !$this->isSettingsMode) {
            $this->initializeFromSettings();
            $this->isInitialized = true;
        }
    }

    /**
     * Settings-dən konfiqurasiyaları yükləyir
     */
    private function initializeFromSettings(): void
    {
        try {
            if ($this->isSettingsMode) {
                $this->allowedFormats = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
                $this->quality = 85;
                $this->defaultImage = 'default_photo.webp';
                return;
            }

            // Format tənzimləmələri
            $uploadSettings = setting('upload.allowed_file_types');
            if ($uploadSettings && isset($uploadSettings['image'])) {
                $this->allowedFormats = $uploadSettings['image'];
            } else {
                $this->allowedFormats = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
            }

            // Raster və vector formatları ayırırıq
            $this->rasterFormats = array_filter($this->allowedFormats, fn($format) => $format !== 'svg');
            $this->vectorFormats = in_array('svg', $this->allowedFormats) ? ['svg'] : [];

            // Şəkil keyfiyyəti
            $this->quality = setting('upload.image_quality', 85);

            $infoSettings = setting('info');
            $this->defaultImage = $infoSettings['default_image'] ?? 'default_photo.webp';

            $uploadSettings = setting('upload');

            // Watermark tənzimləmələri
            if (isset($uploadSettings['watermark'])) {
                $this->watermarkEnabled = $uploadSettings['watermark']['enabled'] ?? false;
                $this->watermarkPosition = $uploadSettings['watermark']['position'] ?? 'bottom-right';
                $this->watermarkOpacity = $uploadSettings['watermark']['opacity'] ?? 50;

                // Əgər watermark aktivdirsə və info settings-də watermark şəkli varsa
                if ($this->watermarkEnabled && isset($infoSettings['watermark'])) {
                    $this->watermarkSettings = [
                        'enabled' => true,
                        'image' => $infoSettings['watermark'],
                        'position' => $this->watermarkPosition,
                        'opacity' => $this->watermarkOpacity
                    ];
                }
            }
        }
        catch (\Exception $e) {
            $this->setDefaultValues();
        }
    }

    /**
     * Upload qovluğunu yaradır
     */
    private function createUploadPath(): void
    {
        $uploadPath = $this->driver === 's3' ?
            $this->mainFolder :
            public_path($this->mainFolder);

        if ($this->driver === 's3') {
            if (!Storage::exists($uploadPath)) {
                Storage::makeDirectory($uploadPath);
            }
        } else {
            if (!File::isDirectory($uploadPath)) {
                File::makeDirectory($uploadPath, 0777, true, true);
            }
        }
    }

    // Setter metodları
    public function setFile($file): self
    {
        $this->file = $file;
        return $this;
    }

    public function setName(string $val): self
    {
        $this->name = $val;
        return $this;
    }

    public function setPath(string $val): self
    {
        $this->path = $val;
        return $this;
    }

    public function setRemoveFile(?string $name): self
    {
        $this->remove = $name;
        return $this;
    }

    public function setBase64(bool $val): self
    {
        $this->base64 = $val;
        return $this;
    }

    public function setUrl(bool $val): self
    {
        $this->url = $val;
        return $this;
    }

    public function setQuality(int $val = 85): self
    {
        $this->quality = $val;
        return $this;
    }

    public function setRotate(int $val): self
    {
        $this->rotate = $val;
        return $this;
    }

    public function setSize(int $width, int $height): self
    {
        $this->size = ['width' => $width, 'height' => $height];
        return $this;
    }

    public function setApplyWatermark(bool $value): self
    {
        $this->applyWatermark = $value;
        return $this;
    }

    public function setSettingsMode(bool $value): self
    {
        $this->isSettingsMode = $value;
        $this->setDefaultValues();
        return $this;
    }

    public function setWatermark(
        string $text,
        int    $size = 20,
        string $color = '#ffffff',
        string $position = 'bottom-right',
        int    $x = 10,
        int    $y = 10
    ): self
    {
        $this->watermark[] = [
            'text' => $text,
            'size' => $size,
            'color' => $color,
            'position' => $position,
            'x' => $x,
            'y' => $y,
        ];
        return $this;
    }

    public function setThumbnail(int $width, int $height): self
    {
        $this->thumbnail = ['width' => $width, 'height' => $height];
        return $this;
    }

    public function setMedium(int $width, int $height): self
    {
        $this->medium = ['width' => $width, 'height' => $height];
        return $this;
    }

    public function setLarge(int $width, int $height): self
    {
        $this->large = ['width' => $width, 'height' => $height];
        return $this;
    }

    /**
     * Şəkil yükləmə prosesini başladır
     */
    public function upload(): ?string
    {
        $this->ensureInitialized();

        if (!$this->file) {
            return null;
        }

        try {
            if ($this->url) {
                return $this->handleUrlUpload();
            } elseif ($this->base64) {
                return $this->handleBase64Upload();
            } else {
                return $this->handleFileUpload();
            }
        } catch (\Exception $e) {
            // Xəta baş verərsə null qaytarırıq
            return null;
        }
    }

    /**
     * URL-dən şəkil yükləməni emal edir
     */
    private function handleUrlUpload(): ?string
    {
        $content = @file_get_contents($this->file);
        if ($content === false) {
            return null;
        }

        $extension = strtolower(pathinfo($this->file, PATHINFO_EXTENSION));

        return in_array($extension, $this->vectorFormats)
            ? $this->handleSvgUpload($content)
            : $this->handleRasterUpload($content);
    }

    /**
     * Base64 şəkil yükləməni emal edir
     */
    private function handleBase64Upload(): ?string
    {
        $content = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $this->file));
        return $this->handleRasterUpload($content);
    }

    /**
     * Normal fayl yükləməni emal edir
     */
    private function handleFileUpload(): ?string
    {
        if (!$this->file instanceof UploadedFile) {
            return null;
        }

        $extension = $this->file->getClientOriginalExtension();
        $content = $this->file->get();

        return in_array($extension, $this->vectorFormats)
            ? $this->handleSvgUpload($content)
            : $this->handleRasterUpload($content);
    }

    /**
     * SVG fayllarını emal edir
     */
    private function handleSvgUpload(string $content): ?string
    {
        if (!in_array('svg', $this->allowedFormats) || !str_contains($content, '<svg')) {
            return null;
        }

        $name = $this->generateName('svg');
        $filePath = '/' . $this->path . '/' . $name;

        $this->ensureDirectoryExists('/' . $this->path);
        $this->saveFile($filePath, $content);

        if ($this->remove && ($name !== $this->remove)) {
            $this->delete($this->path, $this->remove);
        }

        return $name;
    }

    /**
     * Raster şəkilləri emal edir
     */
    private function handleRasterUpload(string $content): ?string
    {
        $image = $this->manager->read($content);
        $extension = $this->getExtension($image);

        if (!in_array($extension, $this->rasterFormats)) {
            return null;
        }

        $name = $this->generateName($extension);
        $filePath = '/' . $this->path . '/' . $name;

        $this->ensureDirectoryExists('/' . $this->path);
        $this->processImage($image, $filePath);
        $this->createResizedVersions($image, $name);

        if ($this->remove && ($name !== $this->remove)) {
            $this->delete($this->path, $this->remove);
        }

        return $name;
    }

    /**
     * Şəkil formatını təyin edir
     */
    private function getExtension(ImageInterface $image): string
    {
        $mime = $image->encodeByMediaType()->mediaType();
        $extension = match ($mime) {
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            default => 'jpg',
        };

        return in_array($extension, $this->allowedFormats) ? $extension : 'jpg';
    }

    /**
     * Şəkil adını generasiya edir
     */
    private function generateName(string $extension): string
    {
        return ($this->name ?
                Str::slug($this->name) :
                Str::random(20) . time()) . '.' . $extension;
    }

    /**
     * Əsas şəkil emalını həyata keçirir
     */
    private function processImage(ImageInterface $image, string $filePath): void
    {
        if ($this->size) {
            $image = $image->resize($this->size['width'], $this->size['height']);
        }

        if ($this->rotate) {
            $image = $image->rotate($this->rotate);
        }

        if ($this->applyWatermark) {
            if (count($this->watermark) > 0) {
                foreach ($this->watermark as $wm) {
                    $image = $image->text(
                        $wm['text'],
                        $wm['x'],
                        $wm['y'],
                        function ($font) use ($wm) {
                            $font->size($wm['size']);
                            $font->color($wm['color']);
                            $font->align($wm['position']);
                            $font->valign($wm['position']);
                        }
                    );
                }
            } else if ($this->watermarkSettings['enabled'] ?? false) {
                $image = $this->applyWatermarkFromSettings($image);
                \Log::info('Final image details:', [
                    'size' => [
                        'width' => $image->width(),
                        'height' => $image->height()
                    ]
                ]);
            }
        }


        $encoder = $this->getEncoder($this->getExtension($image));
        $encoded = $image->encode($encoder);
        $this->saveFile($filePath, $encoded);
    }

    /**
     * Settings-dən gələn watermark-ı tətbiq edir
     */
    private function applyWatermarkFromSettings(ImageInterface $image): ImageInterface
    {
        try {
            $watermarkPath = public_path($this->mainFolder . '/setting/' . $this->watermarkSettings['image']);


            if (!file_exists($watermarkPath)) {
                return $image;
            }

            // Watermark şəklini oxuyuruq və log edirik
            $watermarkContent = file_get_contents($watermarkPath);
            $watermark = $this->manager->read($watermarkContent);

            // Watermark ölçüsünü hesablayırıq
            $maxWidth = (int)($image->width() * 0.3);
            $maxHeight = (int)($image->height() * 0.3);

            // Watermark-ı yenidən ölçüləndiririk
            $watermark = $watermark->scaleDown($maxWidth, $maxHeight);

            $opacity = $this->watermarkSettings['opacity'] ?? 50;


            // Watermark-ı əlavə edirik (pozisiyaları düzgün şəkildə ötürürük)
            return $image->place(
                $watermark,
                $this->watermarkSettings['position'] ?? 'bottom-right',
                20,
                20,
                $opacity
            );

        } catch (\Exception $e) {
            \Log::error('Watermark application failed:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $image;
        }
    }

    /**
     * Watermark-ın dəqiq pozisiyasını hesablayır.
     *
     * @param int $imageWidth Əsas şəklin eni
     * @param int $imageHeight Əsas şəklin hündürlüyü
     * @param int $watermarkWidth Watermark şəklinin eni
     * @param int $watermarkHeight Watermark şəklinin hündürlüyü
     * @param string $position Watermark-ın yerləşmə pozisiyası
     * @return array X və Y koordinatları
     */
    private function calculateWatermarkPosition(
        int    $imageWidth,
        int    $imageHeight,
        int    $watermarkWidth,
        int    $watermarkHeight,
        string $position
    ): array
    {
        // Kənarlardan məsafə (padding)
        $padding = 20;

        // Mərkəz nöqtələrini hesablayırıq
        $centerX = ($imageWidth - $watermarkWidth) / 2;
        $centerY = ($imageHeight - $watermarkHeight) / 2;

        return match ($position) {
            // Yuxarı pozisiyalar
            'top-left' => [
                'x' => $padding,
                'y' => $padding
            ],
            'top-center' => [
                'x' => $centerX,
                'y' => $padding
            ],
            'top-right' => [
                'x' => $imageWidth - $watermarkWidth - $padding,
                'y' => $padding
            ],

            // Mərkəz pozisiyası
            'center' => [
                'x' => $centerX,
                'y' => $centerY
            ],

            // Aşağı pozisiyalar
            'bottom-left' => [
                'x' => $padding,
                'y' => $imageHeight - $watermarkHeight - $padding
            ],
            'bottom-center' => [
                'x' => $centerX,
                'y' => $imageHeight - $watermarkHeight - $padding
            ],
            // Default olaraq bottom-right
            default => [
                'x' => $imageWidth - $watermarkWidth - $padding,
                'y' => $imageHeight - $watermarkHeight - $padding
            ]
        };
    }

    /**
     * Fərqli ölçülərdə versiyaları yaradır
     */
    private function createResizedVersions(ImageInterface $image, string $name): void
    {
        $versions = [
            'thumbnail' => $this->thumbnail,
            'medium' => $this->medium,
            'large' => $this->large
        ];

        foreach ($versions as $type => $size) {
            if ($size) {
                $resized = $image->resize($size['width'], $size['height']);
                $filePath = '/' . $this->path . '/' . $type . '/' .
                    pathinfo($name, PATHINFO_FILENAME) . '.webp';

                $this->ensureDirectoryExists('/' . $this->path . '/' . $type);

                $encoder = new WebpEncoder(quality: $this->quality);
                $encoded = $resized->encode($encoder);

                $this->saveFile($filePath, $encoded);
            }
        }
    }

    /**
     * Qovluğun mövcudluğunu yoxlayır və yaradır
     */
    private function ensureDirectoryExists(string $path): void
    {
        if ($this->driver === 's3') {
            return; // S3 doesn't need directory creation
        }

        $directory = public_path($this->mainFolder . $path);
        if (!File::isDirectory($directory)) {
            File::makeDirectory($directory, 0777, true, true);
        }
    }

    /**
     * Faylı saxlayır
     */
    private function saveFile(string $path, $content): void
    {
        if ($this->driver === 's3') {
            Storage::put($this->mainFolder . $path, $content);
        } else {
            File::put(public_path($this->mainFolder . $path), $content);
        }
    }

    /**
     * Format üçün encoder seçir
     */
    private function getEncoder(string $format): JpegEncoder|PngEncoder|WebpEncoder|GifEncoder
    {
        return match ($format) {
            'png' => new PngEncoder(),
            'webp' => new WebpEncoder($this->quality),
            'gif' => new GifEncoder(),
            default => new JpegEncoder($this->quality),
        };
    }

    /**
     * Şəkili silir
     */
    public function delete(string $path, string $name): bool
    {
        try {
            $mainPath = $this->driver === 's3' ?
                $this->mainFolder . '/' :
                public_path($this->mainFolder . '/');

            $path = preg_replace('/\/\d{2}-\d{2}-\d{4}/', '', ltrim($path, '/'));
            $originalFile = $mainPath . $path . '/' . $name;


            $this->deleteFile($originalFile);

            // Bütün versiyaları silirik
            $versions = ['thumbnail', 'medium', 'large'];

            $isControlSlash = str($name)->contains('/');

            foreach ($versions as $version) {

                $stringPath = $mainPath . $path;
                if ($isControlSlash) {
                    $explodeName = explode('/', $name);
                    $name = end($explodeName);
                    $subFolder = $explodeName[0];
                    $versionFile = $stringPath . '/' . $subFolder . '/' . $version . '/' . $name;
                }
                else {
                    $versionFile = $mainPath . $path . '/' . $version . '/' . $name;
                }

                $this->deleteFile($versionFile);
            }

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Tək faylı silir
     */
    private function deleteFile(string $file): void
    {
        if ($this->driver === 's3') {
            if (Storage::exists($file)) {
                Storage::delete($file);
            }
        } else {
            if (File::isFile($file)) {
                File::delete($file);
            }
        }
    }

    /**
     * Şəklin bütün versiyalarının URL-lərini qaytarır
     */
    public function getPhoto(
        string  $path,
        string  $name,
        ?string $customDefaultImage = null
    ): array
    {
        $this->ensureInitialized();
        $result = [];

        // Əgər fayl adı null və ya boşdursa, birbaşa default şəklə yönləndirir
        if (empty($name)) {
            $defaultUrl = $this->getDefaultImageUrl($customDefaultImage);
            return [
                'original' => $defaultUrl,
                'thumbnail' => $defaultUrl,
                'medium' => $defaultUrl,
                'large' => $defaultUrl
            ];
        }

        $nameWithoutExtension = pathinfo($name, PATHINFO_FILENAME);
        $stringPath = $this->mainFolder . '/' . ltrim($path, '/');
        $originalFile = $stringPath . '/' . $name;
        $originalUrl = $this->getFileUrl($originalFile);

        // Original şəkil yoxlaması və default şəklə yönləndirmə
        $result['original'] = $this->fileExists($originalFile)
            ? $originalUrl
            : $this->getDefaultImageUrl($customDefaultImage);

        // Versiyaları yoxlayırıq
        $versions = ['thumbnail', 'medium', 'large'];
        foreach ($versions as $version) {
            $isControlSlash = str($name)->contains('/');

            if ($isControlSlash) {
                $explodeName = explode('/', $name);
                $name = end($explodeName);
                $subFolder = $explodeName[0];
                $versionFile = $stringPath . '/' . $subFolder . '/' . $version . '/' . $name;
            }
            else {
                $versionFile = $stringPath . '/' . $version . '/' . $name;
            }

            if ($this->fileExists($versionFile)) {
                $result[$version] = $this->getFileUrl($versionFile);
            } else {
                // Versiya mövcud deyilsə, default şəkli istifadə edirik
                $result[$version] = $this->getDefaultImageUrl($customDefaultImage);
            }
        }

        return $result;
    }

    /**
     * Default şəkil URL-ni qaytarır
     * Custom default şəkil və ya settings-dəki default şəkli istifadə edir
     */
    private function getDefaultImageUrl(?string $customDefault = null): string
    {
        $defaultImageName = $customDefault ?? $this->defaultImage;
        return $this->getFileUrl($this->mainFolder . '/setting/' . $defaultImageName);
    }

    /**
     * Fayl URL-ni qaytarır
     * S3 və local fayllar üçün fərqli URL formatı istifadə edir
     */
    private function getFileUrl(string $file): string
    {
        return $this->driver === 's3' ?
            Storage::url(ltrim($file, '/')) :
            url($file);
    }

    /**
     * Faylın mövcudluğunu yoxlayır
     * S3 və local fayllar üçün fərqli yoxlama metodları istifadə edir
     */
    private function fileExists(string $file): bool
    {
        return $this->driver === 's3' ?
            Storage::exists($file) :
            File::isFile(public_path($file));
    }
}
