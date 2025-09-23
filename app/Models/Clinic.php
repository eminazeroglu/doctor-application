<?php

namespace App\Models;

use App\Traits\Model\HasImage;
use App\Traits\Model\HasTranslate;
use Illuminate\Database\Eloquent\Casts\Attribute as AttributeAlias;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Clinic extends BaseModel
{
    use HasImage, SoftDeletes, HasTranslate;

    /**
     * Kütləvi təyin edilə bilən atributlar.
     * @var array
     */
    protected $fillable = [
        'uuid',
        'slug',
        'translates',
        'city_id',
        'region_id',
        'country_id',
        'postal_code',
        'phone',
        'email',
        'website',
        'latitude',
        'longitude',
        'working_hours',
        'facilities',
        'logo_path',
        'images',
        'rating',
        'ratings_count',
        'is_verified',
        'is_featured',
        'is_active',
        'created_by',
        'parent_id'
    ];

    /**
     * Verilənlər tipini çevrilməli olan atributlar.
     * @var array
     */
    protected $casts = [
        'working_hours' => 'json',
        'facilities' => 'json',
        'images' => 'json',
        'rating' => 'integer',
        'ratings_count' => 'integer',
        'is_verified' => 'boolean',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    /**
     * Avtomatik əlavə edilən atributlar.
     * @var array
     */
    protected $appends = ['average_rating', 'logo'];

    /**
     * Slug mənbə sütunu
     * @return string
     */
    protected function getSlugSourceColumn(): string
    {
        return 'name';
    }

    public function getTranslatableAttributes(): array
    {
        return [
            'name',
            'description',
            'address',
        ];
    }

    /**
     * Orta qiymətləndirməni hesablayır.
     * @return AttributeAlias
     */
    public function averageRating(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                return $this->ratings_count > 0 ? round($this->rating / $this->ratings_count, 1) : 0;
            }
        );
    }

    public function getImageFields(): array
    {
        return [
            'logo_path' => [
                'path' => 'clinic'
            ]
        ];
    }

    public function logo(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                return $this->getImageUrl('logo_path');
            }
        );
    }

    /**
     * Klinikanın iş saatlarını qaytarır.
     * @return HasMany
     */
    public function workingHours(): HasMany
    {
        return $this->hasMany(ClinicWorkingHour::class);
    }

    /**
     * Klinikanın ixtisaslarını qaytarır.
     * @return BelongsToMany
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'clinic_categories')
            ->withPivot(['description', 'is_active'])
            ->withTimestamps();
    }

    /**
     * Klinikanın xidmətlərini qaytarır.
     * @return BelongsToMany
     */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'clinic_services')
            ->withPivot(['price', 'duration', 'description', 'is_active'])
            ->withTimestamps();
    }

    /**
     * Klinikanın tətil günlərini qaytarır.
     * @return HasMany
     */
    public function holidays(): HasMany
    {
        return $this->hasMany(ClinicHoliday::class);
    }

    /**
     * Klinikanın əsas klinikasını qaytarır.
     * @return BelongsTo
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Clinic::class, 'parent_id');
    }

    /**
     * Klinikanın yaradıcı istifadəçisini qaytarır.
     * @return BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Klinikada çalışan həkimləri qaytarır.
     * @return BelongsToMany
     */
    public function doctors(): BelongsToMany
    {
        return $this->belongsToMany(Doctor::class, 'doctor_clinic')
            ->withPivot(['is_main_workplace', 'is_active', 'note'])
            ->withTimestamps();
    }

    /**
     * Klinikaya aid randevuları qaytarır.
     * @return HasMany
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Klinika haqqında rəyləri qaytarır.
     * @return HasMany
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Klinikanın aid olduğu ölkə.
     * @return BelongsTo
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Klinikanın aid olduğu şəhər.
     * @return BelongsTo
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /**
     * Klinikanın aid olduğu rayon.
     * @return BelongsTo
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    /**
     * Klinikanı favori seçən xəstələri qaytarır.
     * @return BelongsToMany
     */
    public function favoriteByPatients(): BelongsToMany
    {
        return $this->belongsToMany(Patient::class, 'patient_favorite_clinics')
            ->withPivot(['note'])
            ->withTimestamps();
    }

    /**
     * Aktiv və təsdiqlənmiş həkimləri qaytarır.
     * @return BelongsToMany
     */
    public function activeVerifiedDoctors(): BelongsToMany
    {
        return $this->doctors()
            ->where('is_active', true)
            ->where('is_verified', true);
    }
}
