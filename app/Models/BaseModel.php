<?php

namespace App\Models;

use App\Traits\Model\HasImage;
use App\Traits\Model\HasSlug;
use App\Traits\Model\HasTranslate;
use App\Traits\Model\HasUuid;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

abstract class BaseModel extends Model
{
    // Traits
    use HasFactory, HasUuid, HasSlug;

    // Properties
    protected $guarded = ['id', 'uuid'];

    protected static array $columnsCache = [];

    private array $tableColumns = [];

    public bool $uploadBase64 = false;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->loadTableColumns();

        $this->initializeModelAttributes();
    }

    protected function loadTableColumns(): void
    {
        $tableName = $this->getTable();

        if (!isset(static::$columnsCache[$tableName])) {
            $cacheKey = "table.columns.{$tableName}";

            static::$columnsCache[$tableName] = Cache::remember($cacheKey, now()->addWeek(), function() use($tableName) {
                $dbName = config('database.connections.' . config('database.default') . '.database');

                try {
                    return DB::table('information_schema.columns')
                        ->select('column_name')
                        ->where('table_schema', $dbName)
                        ->where('table_name', $tableName)
                        ->pluck('column_name')
                        ->toArray();

                } catch (\Exception $e) {
                    // Fallback olaraq Schema::getColumnListing() istifadə edirik
                    Log::warning("Could not fetch columns from information_schema for {$tableName}");
                    return Schema::getColumnListing($tableName);
                }
            });
        }

        $this->tableColumns = static::$columnsCache[$tableName];
    }


    protected function initializeModelAttributes(): void
    {
        if (in_array('uuid', $this->tableColumns) &&
            !in_array(HasUuid::class, class_uses_recursive($this))) {
            $this->addTrait(HasUuid::class);
        }

        // Sütunları yoxlayırıq
        if (in_array('translates', $this->tableColumns)) {
            $this->casts['translates'] = 'json';
        }

        if (in_array('deleted_at', $this->tableColumns) &&
            !in_array(SoftDeletes::class, class_uses_recursive($this))) {
            $this->addTrait(SoftDeletes::class);
        }

        if (in_array('created_by', $this->tableColumns)) {
            $this->fillable[] = 'created_by';
        }

        if (in_array('updated_by', $this->tableColumns)) {
            $this->fillable[] = 'updated_by';
        }

        // HasGalleries trait-i modeldə varsa initialize metodunu çağırırıq
        $traits = class_uses_recursive($this);
        if (isset($traits[\App\Traits\Model\HasGalleries::class]) &&
            method_exists($this, 'initializeHasGalleries')) {
            $this->initializeHasGalleries();
        }

        $this->initializeHasImage();
    }

    protected static function bootHasUuid(): void
    {
        static::creating(function (Model $model) {
            // Cədvəldə uuid sütunu varsa və dəyər təyin olunmayıbsa
            if ($model->tableHasColumn('uuid') && !$model->uuid) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    // Migration baş verəndə cache-i təmizləmək üçün
    protected static function bootBaseModel(): void
    {
        parent::boot();

        if (app()->runningInConsole()) {
            static::clearColumnsCache();
        }
    }

    protected function tableHasColumn(string $column): bool
    {
        return in_array($column, $this->tableColumns);
    }

    // Cache-i təmizləmək üçün helper method
    public static function clearColumnsCache(): void
    {
        foreach (static::$columnsCache as $tableName => $columns) {
            Cache::forget("table.columns.{$tableName}");
        }
        static::$columnsCache = [];
    }

    protected function addTrait(string $trait): void
    {
        $class = static::class;

        if (!in_array($trait, class_uses_recursive($class))) {
            class_uses($class, $trait);
        }
    }

    protected function initializeHasImage(): void
    {
        if (in_array(HasImage::class, class_uses_recursive($this))) {
            if (method_exists($this, 'setUseBase64') && $this->uploadBase64) {
                $this->setUseBase64(true);
            }
        }
    }

    // Boot method
    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (
                !$model->isDirty('created_by') &&
                Auth::check() &&
                $model->tableHasColumn('created_by')
            ) {
                $model->created_by = Auth::id();
                $model->created_by_name = Auth::user()->fullname;
            }

            if ($model->tableHasColumn('order')) {
                $lastOrderId = static::query()->max('order') ?? 0;
                $model->order = $lastOrderId + 1;
            }
        });

        static::updating(function (self $model): void {
            if (
                !$model->isDirty('updated_by') &&
                Auth::check() &&
                $model->tableHasColumn('updated_by')
            ) {
                $model->updated_by = Auth::id();
                $model->updated_by_name = Auth::user()->fullname;
            }
        });

    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOwned($query)
    {
        return $query->where('created_by', Auth::id());
    }

    // Relationships
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Attribute accessors
    protected function createdAt(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? Carbon::parse($value)->toIso8601String() : null,
        );
    }

    protected function updatedAt(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? Carbon::parse($value)->toIso8601String() : null,
        );
    }

    public function photo(): Attribute
    {
        return new Attribute(
            get: function() {
                // Əgər HasImage trait-i varsa və getImageUrl metodu mövcuddursa
                if (in_array(HasImage::class, class_uses_recursive($this)) && method_exists($this, 'getImageUrl')) {
                    return $this->getImageUrl('photo_path');
                }
                // Əgər HasTranslate trait-i varsa və getTranslation metodu mövcuddursa
                elseif (in_array(HasTranslate::class, class_uses_recursive($this)) &&
                    method_exists($this, 'getTranslatableImageFields') &&
                    method_exists($this, 'getTranslation')) {

                    $imageFields = $this->getTranslatableImageFields();

                    // Əgər şəkil sahələri təyin edilmişsə
                    if (!empty($imageFields)) {
                        // İlk şəkil sahəsinin açarını və parametrlərini əldə edirik
                        $firstImageKey = array_key_first($imageFields);
                        $imageConfig = $imageFields[$firstImageKey];

                        // İlk növbədə URL sahəsini yoxlayırıq
                        $urlField = $firstImageKey . '_url';
                        $photoUrl = $this->getTranslation($urlField);
                        if ($photoUrl) {
                            return $photoUrl;
                        }

                        // Şəkil adını əldə edirik
                        $photoName = $this->getTranslation($firstImageKey);
                        if ($photoName) {
                            // Əgər şəkil adı varsa və bu, artıq tam URL deyilsə
                            if (!filter_var($photoName, FILTER_VALIDATE_URL)) {
                                // Şəkil yolunu təyin edirik
                                $path = $imageConfig['path'] ?? $this->getTable();

                                // ImageUploadService vasitəsi ilə tam URL əldə edirik
                                $imageService = new \App\Services\App\Upload\ImageUploadService();
                                $photoUrls = $imageService->getPhoto(
                                    $path,
                                    $photoName,
                                    $imageConfig['default_image'] ?? 'default_photo.webp'
                                );

                                return $photoUrls['original'];
                            }

                            // Əgər photoName artıq tam URL-dirsə, onu qaytarırıq
                            return $photoName;
                        }
                    }
                }
                // Heç biri yoxdursa, default dəyəri qaytaraq
                else {
                    return url('uploads/photos/setting/default_photo.webp');
                }
            }
        );
    }

    // Helper methods
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function getCustomField($name)
    {
        if ($this->tableHasColumn('custom_fields')) {
            return $this->customFields[$name] ?? null;
        }
        return null;
    }
}
