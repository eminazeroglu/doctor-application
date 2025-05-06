<?php

namespace App\Traits\Model;

use App\Services\App\Upload\FileUploadService;
use Illuminate\Http\UploadedFile;

trait HasFile
{
    /**
     * Trait-i boot edir
     */
    public static function bootHasFile(): void
    {
        // Model yadda saxlanılanda faylları upload edir
        static::saving(function ($model) {
            $model->uploadFiles();
        });

        // Model silindikdə faylları silir
        static::deleting(function ($model) {
            $model->deleteFiles();
        });
    }

    /**
     * Model-də olan fayl sahələrini təyin edir
     *
     * @return array
     * [
     *      'document' => [
     *          'path' => 'documents',
     *          'multiple' => true|false,
     *          'allowed_types' => ['pdf', 'doc'] // null: settings-dən istifadə et
     *      ]
     * ];
     */
    abstract public function getFileFields(): array;

    /**
     * Faylları upload edir
     */
    public function uploadFiles(): void
    {
        $fileService = new FileUploadService();

        foreach ($this->getFileFields() as $field => $config) {
            if ($this->isFileFieldUpdated($field)) {
                // Multiple fayllar üçün
                if (!empty($config['multiple'])) {
                    $files = $this->getMultipleFiles($field);
                    $fileNames = [];

                    foreach ($files as $file) {
                        if ($file instanceof UploadedFile && $file->isValid()) {
                            $fileName = $this->uploadSingleFile($fileService, $file, $config);
                            if ($fileName) {
                                $fileNames[] = $fileName;
                            }
                        }
                    }

                    // Köhnə faylları silmək
                    if (!empty($this->getOriginal($field)) && is_array($this->getOriginal($field))) {
                        foreach ($this->getOriginal($field) as $oldFile) {
                            if (!in_array($oldFile, $fileNames)) {
                                $fileService->delete($config['path'], $oldFile);
                            }
                        }
                    }

                    if (!empty($fileNames)) {
                        $this->attributes[$field] = $fileNames;
                    }
                }
                // Tək fayl üçün
                else {
                    $file = $this->getFile($field);
                    if ($file instanceof UploadedFile && $file->isValid()) {
                        $fileName = $this->uploadSingleFile($fileService, $file, $config);
                        if ($fileName) {
                            // Köhnə faylı silirik
                            if (!empty($this->getOriginal($field))) {
                                $fileService->delete($config['path'], $this->getOriginal($field));
                            }
                            $this->attributes[$field] = $fileName;
                        }
                    }
                }
            }
        }
    }

    /**
     * Tək faylı upload edir
     */
    protected function uploadSingleFile(FileUploadService $fileService, UploadedFile $file, array $config): ?string
    {
        return $fileService
            ->setFile($file)
            ->setPath($config['path'])
            ->setAllowedTypes($config['allowed_types'] ?? null)
            ->upload();
    }

    /**
     * Faylları silir
     */
    public function deleteFiles(): void
    {
        $fileService = new FileUploadService();

        foreach ($this->getFileFields() as $field => $config) {
            if (!empty($config['multiple'])) {
                // Multiple fayllar üçün
                if (isset($this->attributes[$field]) && is_array($this->attributes[$field])) {
                    foreach ($this->attributes[$field] as $file) {
                        $fileService->delete($config['path'], $file);
                    }
                }
            } else {
                // Tək fayl üçün
                if (!empty($this->attributes[$field])) {
                    $fileService->delete($config['path'], $this->attributes[$field]);
                }
            }
        }
    }

    /**
     * Multiple faylları əldə edir
     */
    protected function getMultipleFiles(string $field): array
    {
        $files = request()->file($field) ?? [];
        return is_array($files) ? $files : [$files];
    }

    /**
     * Tək faylı əldə edir
     */
    protected function getFile(string $field): ?UploadedFile
    {
        return request()->file($field);
    }

    /**
     * Fayl sahəsinin yenilənib-yenilənmədiyini yoxlayır
     */
    protected function isFileFieldUpdated(string $field): bool
    {
        return $this->isDirty($field) || request()->hasFile($field);
    }

    /**
     * Faylın URL-ni əldə edir
     */
    public function getFileUrl(string $field): string|array|null
    {
        if (!isset($this->attributes[$field])) {
            return null;
        }

        $fileService = new FileUploadService();
        $config = $this->getFileFields()[$field];

        // Multiple fayllar üçün
        if (!empty($config['multiple']) && is_array($this->attributes[$field])) {
            $urls = [];
            foreach ($this->attributes[$field] as $file) {
                $urls[] = $fileService->getFileUrl($config['path'], $file);
            }
            return $urls;
        }

        // Tək fayl üçün
        return $fileService->getFileUrl($config['path'], $this->attributes[$field]);
    }

    /**
     * Faylın mövcudluğunu yoxlayır
     */
    public function fileExists(string $field): bool|array
    {
        if (!isset($this->attributes[$field])) {
            return false;
        }

        $fileService = new FileUploadService();
        $config = $this->getFileFields()[$field];

        // Multiple fayllar üçün
        if (!empty($config['multiple']) && is_array($this->attributes[$field])) {
            $exists = [];
            foreach ($this->attributes[$field] as $file) {
                $exists[] = $fileService->exists($config['path'], $file);
            }
            return $exists;
        }

        // Tək fayl üçün
        return $fileService->exists($config['path'], $this->attributes[$field]);
    }

    /**
     * Faylın ölçüsünü qaytarır (KB)
     */
    public function getFileSize(string $field): float|array|null
    {
        if (!isset($this->attributes[$field])) {
            return null;
        }

        $fileService = new FileUploadService();
        $config = $this->getFileFields()[$field];

        // Multiple fayllar üçün
        if (!empty($config['multiple']) && is_array($this->attributes[$field])) {
            $sizes = [];
            foreach ($this->attributes[$field] as $file) {
                $sizes[] = $fileService->getFileSize($config['path'], $file);
            }
            return $sizes;
        }

        // Tək fayl üçün
        return $fileService->getFileSize($config['path'], $this->attributes[$field]);
    }
}
