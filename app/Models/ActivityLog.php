<?php

namespace App\Models;

use App\Enums\ActivityLogActionEnum;
use App\Enums\ActivityLogStatusEnum;
use App\Traits\Model\HasUuid;
use Illuminate\Database\Eloquent\Casts\Attribute as AttributeAlias;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ActivityLog extends BaseModel
{
    use HasUuid, SoftDeletes;

    protected $appends = ['action_description', 'status_description'];

    protected $fillable = [
        'model_type',
        'model_id',
        'action',
        'old_data',
        'new_data',
        'ip_address',
        'user_agent',
        'method',
        'url',
        'meta_data',
        'status',
        'error_message'
    ];

    protected $casts = [
        'old_data' => 'json',
        'new_data' => 'json',
        'meta_data' => 'json'
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    /**
     * Log-un aid olduğu model ilə əlaqə (polimorfik)
     */
    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Log-u yaradan istifadəçi ilə əlaqə
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Log-u yeniləyən istifadəçi ilə əlaqə
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /**
     * Uğurlu əməliyyatlar
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', ActivityLogStatusEnum::SUCCESS);
    }

    /**
     * Xətalı əməliyyatlar
     */
    public function scopeErrors($query)
    {
        return $query->where('status', ActivityLogStatusEnum::ERROR);
    }

    /**
     * Müəyyən bir tip əməliyyat üzrə
     */
    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Müəyyən bir istifadəçinin əməliyyatları
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('created_by', $userId);
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    /**
     * Dəyişiklikləri oxunaqlı formada qaytarır
     */
    public function getChanges(): array
    {
        if (!$this->old_data || !$this->new_data) {
            return [];
        }

        $changes = [];
        foreach ($this->new_data as $key => $value) {
            if (isset($this->old_data[$key]) && $this->old_data[$key] !== $value) {
                $changes[$key] = [
                    'old' => $this->old_data[$key],
                    'new' => is_array(json_decode($value, true)) ? json_decode($value, true) : $value
                ];
            }
        }

        return $changes;
    }

    public function actionDescription(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                return $this->action ? ActivityLogActionEnum::getDescription($this->action) : '';
            }
        );
    }

    public function statusDescription(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                return $this->status ? ActivityLogStatusEnum::getDescription($this->status) : '';
            }
        );
    }

    /**
     * Log-un məzmununu insanın başa düşəcəyi formada qaytarır
     */
    public function getDescription(): string
    {
        $creatorName = $this->creator?->name ?? 'System';
        $action = $this->action_description;
        $modelType = class_basename($this->model_type);

        return "{$creatorName} {$action} a {$modelType}";
    }
}
