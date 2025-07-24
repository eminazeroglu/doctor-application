<?php

namespace App\Models;

use App\Traits\Model\HasImage;
use App\Traits\Model\HasSeoLink;
use App\Traits\Model\HasTranslate;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Throwable;

class Category extends BaseModel
{
    use HasImage, HasTranslate, HasSeoLink;

    protected $fillable = [
        'uuid',
        'slug',
        'parent_id',
        'terms_id',
        'translates',
        'icon',
        'photo_path',
        'meta_tags',
        'custom_fields',
        'is_default',
        'is_active',
        'order'
    ];

    protected $casts = [
        'meta_tags' => 'array',
        'custom_fields' => 'object',
        'is_default' => 'boolean',
        'is_active' => 'boolean'
    ];

    protected $appends = ['photo'];

    // Relationships
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function attributes(): HasMany
    {
        return $this->hasMany(CategoryAttribute::class);
    }

    /**
     * İxtisasa aid xidmətlər əlaqəsi.
     * @return HasMany
     */
    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    /**
     * İxtisasa aid klinikalar əlaqəsi.
     * @return BelongsToMany
     */
    public function clinics(): BelongsToMany
    {
        return $this->belongsToMany(Clinic::class, 'clinic_specialties')
            ->withPivot(['description', 'is_active'])
            ->withTimestamps();
    }

    /**
     * İxtisasa aid həkimlər əlaqəsi.
     * @return HasMany
     */
    public function doctors(): HasMany
    {
        return $this->hasMany(Doctor::class, 'specialty');
    }

    public function getImageFields(): array
    {
        return [
            'photo_path' => [
                'path' => 'category',
                'thumbnail' => true,
                'watermark' => true,
                'base64' => true,
            ]
        ];
    }

    public function getTranslatableAttributes(): array
    {
        return [
            'name',
            'description',
        ];
    }

    /**
     * HELPERS
     * @throws Throwable
     */
    public function syncRelations(array $data): void
    {
        DB::transaction(function () use ($data) {
            if (isset($data['categories'])) {
                $this->relateds()->delete();
            }
        });
    }
}
