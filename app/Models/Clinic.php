<?php

namespace App\Models;

use App\Traits\Model\HasImage;
use App\Traits\Model\HasSlug;
use App\Traits\Model\HasTranslate;
use App\Traits\Model\HasUuid;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Clinic extends Model
{
    use HasUuid, HasSlug, HasTranslate, HasImage, SoftDeletes;

    protected $fillable = [
        'uuid',
        'slug',
        'country_id',
        'city_id',
        'region_id',
        'translates',
        'address',
        'phone',
        'email',
        'website',
        'latitude',
        'longitude',
        'working_hours',
        'facilities',
        'logo_path',
        'cover_path',
        'meta_tags',
        'custom_fields',
        'is_verified',
        'is_featured',
        'is_active',
        'order',
        'verified_by',
        'verified_at'
    ];

    protected $casts = [
        'translates' => 'json',
        'working_hours' => 'json',
        'facilities' => 'json',
        'meta_tags' => 'json',
        'custom_fields' => 'json',
        'is_verified' => 'boolean',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'verified_at' => 'datetime',
        'latitude' => 'decimal',
        'longitude' => 'decimal'
    ];

    protected $appends = ['name', 'description', 'logo', 'cover', 'average_rating'];

    /**
     * Çoxdilli sahələr
     */
    public function getTranslatableAttributes(): array
    {
        return [
            'name',
            'description',
            'short_description',
            'address_notes'
        ];
    }

    /**
     * Şəkil konfiqurasiyaları
     */
    public function getImageFields(): array
    {
        return [
            'logo_path' => [
                'path' => 'clinics',
                'default_image' => 'default_clinic_logo.png'
            ],
            'cover_path' => [
                'path' => 'clinics',
                'default_image' => 'default_clinic_cover.png'
            ]
        ];
    }

    /**
     * Orta reyting hesablaması
     */
    protected function averageRating(): Attribute
    {
        return Attribute::make(
            get: function () {
                $reviewsCount = $this->reviews()->where('is_approved', true)->count();

                if ($reviewsCount === 0) {
                    return 0;
                }

                return $this->reviews()
                    ->where('is_approved', true)
                    ->avg('rating') ?: 0;
            }
        );
    }

    /**
     * Lokasiya əlaqələri
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    /**
     * Klinikada çalışan həkimlər
     */
    public function doctors(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'doctor_clinics', 'clinic_id', 'user_id')
            ->withPivot(['is_primary', 'working_hours', 'custom_fields'])
            ->withTimestamps();
    }

    /**
     * Klinika ixtisasları
     */
    public function specialties(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'clinic_specialty', 'clinic_id', 'category_id')
            ->withPivot(['is_primary', 'description', 'custom_fields'])
            ->withTimestamps();
    }

    /**
     * Klinikada təqdim olunan xidmətlər
     */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'clinic_services', 'clinic_id', 'service_id')
            ->withPivot(['custom_price', 'custom_duration', 'is_featured', 'custom_fields'])
            ->withTimestamps();
    }

    /**
     * Klinika şəkilləri
     */
    public function photos(): HasMany
    {
        return $this->hasMany(ClinicPhoto::class)->orderBy('order');
    }

    /**
     * Klinika rəyləri
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(ClinicReview::class);
    }

    /**
     * Klinikada olan randevular
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Klinikada olan boş vaxtlar
     */
    public function availabilities(): HasMany
    {
        return $this->hasMany(DoctorAvailability::class);
    }

    /**
     * Klinikada ixtisaslardan birini verən həkimləri tapır
     */
    public function getDoctorsBySpecialty($specialtyId)
    {
        return $this->doctors()
            ->whereHas('doctorSpecialties', function ($query) use ($specialtyId) {
                $query->where('category_id', $specialtyId);
            })
            ->get();
    }

    /**
     * Klinikada verilən xidməti təqdim edən həkimləri tapır
     */
    public function getDoctorsByService($serviceId)
    {
        return $this->doctors()
            ->whereHas('doctorServices', function ($query) use ($serviceId) {
                $query->where('service_id', $serviceId);
            })
            ->get();
    }

    /**
     * İş saatlarının formatlı şəkildə verilməsi
     */
    public function formattedWorkingHours(): Attribute
    {
        return new Attribute(
            get: function () {
                if (!$this->working_hours) {
                    return [];
                }

                $formatted = [];
                $dayNames = [
                    1 => 'Bazar ertəsi',
                    2 => 'Çərşənbə axşamı',
                    3 => 'Çərşənbə',
                    4 => 'Cümə axşamı',
                    5 => 'Cümə',
                    6 => 'Şənbə',
                    7 => 'Bazar'
                ];

                foreach ($this->working_hours as $day => $hours) {
                    $dayNumber = (int) $day;
                    $dayName = $dayNames[$dayNumber] ?? "Gün {$day}";

                    if (isset($hours['closed']) && $hours['closed']) {
                        $formatted[$dayNumber] = [
                            'day' => $dayName,
                            'hours' => 'Bağlıdır'
                        ];
                        continue;
                    }

                    if (isset($hours['start']) && isset($hours['end'])) {
                        $formatted[$dayNumber] = [
                            'day' => $dayName,
                            'hours' => "{$hours['start']} - {$hours['end']}"
                        ];
                    }
                }

                // Gün nömrəsinə görə sıralayaq
                ksort($formatted);

                return array_values($formatted);
            }
        );
    }

    /**
     * Klinika üçün Google Maps linkini qaytar
     */
    public function googleMapsLink(): Attribute
    {
        return new Attribute(
            get: function () {
                if (!$this->latitude || !$this->longitude) {
                    return null;
                }

                return "https://www.google.com/maps/search/?api=1&query={$this->latitude},{$this->longitude}";
            }
        );
    }
}
