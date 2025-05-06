<?php

namespace App\Rules;

use App\Services\Module\SettingService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class Base64ImageControlRule implements ValidationRule
{
    /**
     * @var SettingService
     */
    protected SettingService $settingService;

    public function __construct()
    {
        $this->settingService = app(SettingService::class);
    }

    /**
     * Şəkil validasiyası
     * - Settings-dən format və həcm məlumatlarını alır
     * - Base64 şəklinin formatını və həcmini yoxlayır
     *
     * @param string $attribute
     * @param mixed $value
     * @param Closure $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Settings-dən lazımi məlumatları alırıq
        $allowedFormats = $this->settingService->get('upload', 'allowed_file_types.image', []);
        $maxFileSize = $this->settingService->get('upload', 'max_image_size', 1024*5); // KB

        // Base64 data hissəsini ayırırıq
        $data = substr($value, strpos($value, ',') + 1);
        $decodedData = base64_decode($data);

        // Formatı yoxlayırıq
        $allowedMimeTypes = collect($allowedFormats)
            ->map(fn($format) => 'image/' . $format)
            ->toArray();

        // Temp fayl yaradıb mime type-ı yoxlayırıq
        $tempFile = tmpfile();
        fwrite($tempFile, $decodedData);
        $mimeType = mime_content_type(stream_get_meta_data($tempFile)['uri']);
        fclose($tempFile);

        // Format yoxlaması
        if (!in_array($mimeType, $allowedMimeTypes)) {
            $fail(str(t('validation.invalidImageType'))
                ->replace(':format', implode(', ', $allowedFormats)));
            return;
        }

        // Həcm yoxlaması (KB-a çeviririk)
        $sizeInKB = ceil(strlen($decodedData) / 1024);
        if ($sizeInKB > $maxFileSize) {
            $fail(str(t('validation.invalidImageSize'))
                ->replace(':size', ceil($maxFileSize / 1024) . 'MB'));
        }
    }
}
