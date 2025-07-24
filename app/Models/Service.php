<?php

namespace App\Models;

use App\Traits\Model\HasImage;
use App\Traits\Model\HasTranslate;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends BaseModel
{
    use HasImage, HasTranslate;

    protected $fillable = [
        'uuid',
        'slug',
        'category_id',
        'translates',
        'price',
        'duration',
        'meta_tags',
        'photo_path',
        'custom_fields',
        'is_popular',
        'is_active',
        'order'
    ];

    protected $casts = [
        'translates' => 'object',
        'meta_tags' => 'array',
        'custom_fields' => 'object',
        'price' => 'decimal:2',
        'duration' => 'integer',
        'is_popular' => 'boolean',
        'is_active' => 'boolean'
    ];

    protected $appends = ['photo', 'formatted_price', 'formatted_duration'];

    // Çoxdilli sahələr
    public function getTranslatableAttributes(): array
    {
        return [
            'name',
            'description',
            'short_description',
            'instructions',
            'preparation'
        ];
    }

    // Şəkil konfiqurasiyası
    public function getImageFields(): array
    {
        return [
            'photo_path' => [
                'path' => 'medical-services',
                'base64' => true,
            ]
        ];
    }

    // Əlaqələr
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Xidməti təklif edən klinikalar əlaqəsi.
     * @return BelongsToMany
     */
    public function clinics(): BelongsToMany
    {
        return $this->belongsToMany(Clinic::class, 'clinic_services')
            ->withPivot(['price', 'duration', 'description', 'is_active'])
            ->withTimestamps();
    }

    /**
     * Xidməti təklif edən həkimlər əlaqəsi.
     * @return BelongsToMany
     */
    public function doctors(): BelongsToMany
    {
        return $this->belongsToMany(Doctor::class, 'doctor_clinic_services')
            ->withPivot(['clinic_id', 'price', 'duration', 'description', 'is_active'])
            ->withTimestamps();
    }

    /**
     * Xidmətə aid randevular əlaqəsi.
     * @return HasMany
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    // Əlavə atributlar
    protected function formattedPrice(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->price ? number_format($this->price, 2) . ' AZN' : null,
        );
    }

    protected function formattedDuration(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->duration) return null;

                if ($this->duration < 60) {
                    return $this->duration . ' dəqiqə';
                }

                $hours = floor($this->duration / 60);
                $minutes = $this->duration % 60;

                if ($minutes > 0) {
                    return $hours . ' saat ' . $minutes . ' dəqiqə';
                }

                return $hours . ' saat';
            },
        );
    }

    // Scope metodları
    public function scopePopular($query)
    {
        return $query->where('is_popular', true);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }
}
