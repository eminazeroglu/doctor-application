<?php

namespace App\Services\App\Upload;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileUploadService
{
    // Əsas konfiqurasiya parametrləri
    private string $driver;
    private string $mainFolder = '/uploads/files';
    private array $allowedTypes = [];
    private int $maxFileSize;
    private bool $isSettingsMode = false;
    private bool $isInitialized = false;

    // Fayl parametrləri
    private ?string $name = null;
    private ?string $path = null;
    private ?UploadedFile $file = null;
    private ?string $remove = null;

    public function __construct()
    {
        // Əsas konfiqurasiyaları qururuq
        $this->driver = config('filesystems.default', 'local');

        // Settings-dən fayl tənzimləmələrini əldə edirik
        $this->setDefaultValues();

        // Upload qovluğunu yaradırıq
        $this->createUploadPath();
    }

    /**
     * Default dəyərləri təyin edir
     */
    private function setDefaultValues(): void
    {
        $this->allowedTypes = ['pdf', 'doc', 'docx', 'xls', 'xlsx'];
        $this->maxFileSize = 10240; // 10MB in KB
    }

    /**
     * Settings-dən konfiqurasiyaları yükləyir
     */
    private function ensureInitialized(): void
    {
        if (!$this->isInitialized && !$this->isSettingsMode) {
            $this->initializeFromSettings();
            $this->isInitialized = true;
        }
    }

    /**
     * Settings-dən konfiqurasiyaları oxuyur
     */
    private function initializeFromSettings(): void
    {
        try {
            if ($this->isSettingsMode) {
                $this->setDefaultValues();
                return;
            }

            // Settings-dən məlumatları alırıq
            $uploadSettings = setting('upload');

            // İcazə verilən fayl tipləri
            if (isset($uploadSettings['allowed_file_types']['document'])) {
                $this->allowedTypes = $uploadSettings['allowed_file_types']['document'];
            }

            // Maksimum fayl ölçüsü
            if (isset($uploadSettings['max_file_size'])) {
                $this->maxFileSize = $uploadSettings['max_file_size'];
            }
        } catch (\Exception $e) {
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
    public function setFile(UploadedFile $file): self
    {
        $this->file = $file;
        return $this;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function setPath(string $path): self
    {
        $this->path = $path;
        return $this;
    }

    public function setRemoveFile(?string $name): self
    {
        $this->remove = $name;
        return $this;
    }

    public function setSettingsMode(bool $value): self
    {
        $this->isSettingsMode = $value;
        return $this;
    }

    /**
     * Faylın yüklənməsi
     */
    public function upload(): ?string
    {
        $this->ensureInitialized();

        if (!$this->file || !$this->file->isValid()) {
            return null;
        }

        try {
            // Faylın formatını yoxlayırıq
            $extension = strtolower($this->file->getClientOriginalExtension());
            if (!in_array($extension, $this->allowedTypes)) {
                throw new \Exception('File type not allowed');
            }

            // Faylın ölçüsünü yoxlayırıq (KB)
            $sizeInKB = $this->file->getSize() / 1024;
            if ($sizeInKB > $this->maxFileSize) {
                throw new \Exception('File size exceeds limit');
            }

            // Fayl adını generasiya edirik
            $fileName = $this->generateFileName($extension);
            $filePath = '/' . $this->path . '/' . $fileName;

            // Qovluğun mövcudluğunu yoxlayırıq
            $this->ensureDirectoryExists('/' . $this->path);

            // Faylı yükləyirik
            $this->saveFile($filePath, $this->file);

            // Köhnə faylı silirik
            if ($this->remove && ($fileName !== $this->remove)) {
                $this->delete($this->path, $this->remove);
            }

            return $fileName;

        } catch (\Exception $e) {
            \Log::error('File upload failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Fayl adını generasiya edir
     */
    private function generateFileName(string $extension): string
    {
        return ($this->name ?
                Str::slug($this->name) :
                Str::random(20) . time()) . '.' . $extension;
    }

    /**
     * Qovluğun mövcudluğunu yoxlayır
     */
    private function ensureDirectoryExists(string $path): void
    {
        if ($this->driver === 's3') {
            return;
        }

        $directory = public_path($this->mainFolder . $path);
        if (!File::isDirectory($directory)) {
            File::makeDirectory($directory, 0777, true, true);
        }
    }

    /**
     * Faylı saxlayır
     */
    private function saveFile(string $path, UploadedFile $file): void
    {
        if ($this->driver === 's3') {
            Storage::putFileAs(
                $this->mainFolder . dirname($path),
                $file,
                basename($path)
            );
        } else {
            $file->move(
                public_path($this->mainFolder . dirname($path)),
                basename($path)
            );
        }
    }

    /**
     * Faylı silir
     */
    public function delete(string $path, string $name): bool
    {
        try {
            $mainPath = $this->driver === 's3' ?
                $this->mainFolder . '/' :
                public_path($this->mainFolder . '/');

            $path = preg_replace('/\/\d{2}-\d{2}-\d{4}/', '', ltrim($path, '/'));
            $filePath = $mainPath . $path . '/' . $name;

            if ($this->driver === 's3') {
                if (Storage::exists($filePath)) {
                    Storage::delete($filePath);
                }
            } else {
                if (File::isFile($filePath)) {
                    File::delete($filePath);
                }
            }

            return true;
        } catch (\Exception $e) {
            \Log::error('File deletion failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Faylın URL-ni qaytarır
     */
    public function getFileUrl(string $path, string $name): ?string
    {
        if (empty($name)) {
            return null;
        }

        $filePath = $this->mainFolder . '/' . ltrim($path, '/') . '/' . $name;

        if ($this->driver === 's3') {
            return Storage::exists($filePath) ? Storage::url($filePath) : null;
        } else {
            $fullPath = public_path($filePath);
            return File::exists($fullPath) ? url($filePath) : null;
        }
    }

    /**
     * Faylın mövcudluğunu yoxlayır
     */
    public function exists(string $path, string $name): bool
    {
        $filePath = $this->mainFolder . '/' . ltrim($path, '/') . '/' . $name;

        return $this->driver === 's3' ?
            Storage::exists($filePath) :
            File::exists(public_path($filePath));
    }

    /**
     * Faylın ölçüsünü qaytarır (KB)
     */
    public function getFileSize(string $path, string $name): ?float
    {
        $filePath = $this->mainFolder . '/' . ltrim($path, '/') . '/' . $name;

        if ($this->driver === 's3') {
            return Storage::exists($filePath) ?
                Storage::size($filePath) / 1024 :
                null;
        } else {
            $fullPath = public_path($filePath);
            return File::exists($fullPath) ?
                File::size($fullPath) / 1024 :
                null;
        }
    }
}
