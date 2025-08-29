<?php

namespace App\Models;

use App\Enums\AppointmentStatusEnum;
use App\Enums\UserStatusEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute as AttributeAlias;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Doctor extends BaseModel
{
    use SoftDeletes;

    /**
     * Kütləvi təyin edilə bilən atributlar.
     * @var array
     */
    protected $fillable = [
        'uuid',
        'user_id',
        'category_id',
        'sub_category_id',
        'biography',
        'consultation_fee',
        'consultation_duration',
        'working_days',
        'is_verified',
        'is_featured',
        'years_of_experience',
        'practice_license_number',
        'title',
        'workplace',
        'social_media_links',
        'available_for_home_visit',
        'available_for_online_consultation',
        'home_visit_fee',
        'online_consultation_fee',
        'average_rating',
        'total_ratings',
        'total_patients'
    ];

    /**
     * Verilənlər tipini çevrilməli olan atributlar.
     * @var array
     */
    protected $casts = [
        'workplace' => 'json',
        'working_days' => 'json',
        'social_media_links' => 'json',
        'is_verified' => 'boolean',
        'is_featured' => 'boolean',
        'available_for_home_visit' => 'boolean',
        'available_for_online_consultation' => 'boolean',
        'consultation_fee' => 'float',
        'home_visit_fee' => 'float',
        'online_consultation_fee' => 'float',
        'consultation_duration' => 'integer',
        'years_of_experience' => 'integer',
        'average_rating' => 'integer',
        'total_ratings' => 'integer',
        'total_patients' => 'integer'
    ];

    /**
     * Avtomatik əlavə edilən atributlar.
     * @var array
     */
    protected $appends = ['full_name_with_title', 'rating_average', 'suggested_by_people'];

    /**
     * Həkimin tam adını titulu ilə birlikdə qaytarır.
     * @return AttributeAlias
     */
    public function fullNameWithTitle(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                $title = $this->title ? $this->title . ' ' : '';
                return $title . $this->user->name . ' ' . $this->user->surname;
            }
        );
    }

    /**
     * Orta qiymətləndirməni hesablayır.
     * @return AttributeAlias
     */
    public function ratingAverage(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                // Oy sayısı yoksa 0 döndür
                if ($this->total_ratings <= 0) {
                    return 0;
                }

                // Toplam puan / oy sayısı ile ortalamayı hesapla
                $raw = $this->average_rating / $this->total_ratings;

                // 5’i geçerse 5 olsun, değilse kendi değeri kalsın
                $capped = min($raw, 5);

                // Virgülden sonra 1 basamağa yuvarla
                return round($capped, 1);
            }
        );
    }

    public function suggestedByPeople(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                $suggested_by_people = 0;
                $suggested_by_people_total = $this->appointments()->count();
                $suggested_by_people_confirmed = $this->appointments()->where('appointment_status', AppointmentStatusEnum::Confirmed)->count();

                if ($suggested_by_people_confirmed > 0 && $suggested_by_people_total > 0) {
                    $suggested_by_people = ceil($suggested_by_people_confirmed * 100 / $suggested_by_people_total);
                }

                return $suggested_by_people;
            }
        );
    }

    /**
     * Həkimə aid istifadəçi əlaqəsi.
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Həkimin əsas ixtisası əlaqəsi.
     * @return BelongsTo
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Həkim atribut dəyərləri
     */
    public function attributes(): HasMany
    {
        return $this->hasMany(DoctorAttributeValue::class);
    }

    /**
     * Həkimin alt ixtisası əlaqəsi.
     * @return BelongsTo
     */
    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Həkimin təhsil məlumatları əlaqəsi.
     * @return HasMany
     */
    public function educations(): HasMany
    {
        return $this->hasMany(DoctorEducation::class);
    }

    /**
     * Həkimin iş təcrübəsi əlaqəsi.
     * @return HasMany
     */
    public function experiences(): HasMany
    {
        return $this->hasMany(DoctorExperience::class);
    }

    /**
     * Həkimin sertifikatları əlaqəsi.
     * @return HasMany
     */
    public function certificates(): HasMany
    {
        return $this->hasMany(DoctorCertificate::class);
    }

    /**
     * Həkimin dil bilikləri əlaqəsi.
     * @return HasMany
     */
    public function languages(): HasMany
    {
        return $this->hasMany(DoctorLanguage::class);
    }

    public function doctorClinics(): HasMany
    {
        return $this->hasMany(DoctorClinic::class);
    }

    /**
     * Həkimin çalışdığı klinikalar əlaqəsi.
     * @return BelongsToMany
     */
    public function clinics(): BelongsToMany
    {
        return $this->belongsToMany(Clinic::class, 'doctor_clinic')
            ->withPivot(['start_date', 'end_date', 'is_main_workplace', 'is_active', 'note'])
            ->withTimestamps();
    }

    /**
     * Həkimin iş cədvəli əlaqəsi.
     * @return HasMany
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(DoctorSchedule::class);
    }

    /**
     * Həkimin məşğulluq vaxtları əlaqəsi.
     * @return HasMany
     */
    public function unavailabilities(): HasMany
    {
        return $this->hasMany(DoctorUnavailability::class);
    }

    /**
     * Həkimin təklif etdiyi xidmətlər əlaqəsi.
     * @return BelongsToMany
     */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'doctor_clinic_services')
            ->withPivot(['price', 'duration', 'description', 'is_active'])
            ->withTimestamps();
    }

    /**
     * Həkimə aid randevular əlaqəsi.
     * @return HasMany
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Həkim haqqında rəylər əlaqəsi.
     * @return HasMany
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Həkimi favori seçən xəstələr əlaqəsi.
     * @return BelongsToMany
     */
    public function favoriteByPatients(): BelongsToMany
    {
        return $this->belongsToMany(Patient::class, 'patient_favorite_doctors')
            ->withPivot(['note'])
            ->withTimestamps();
    }

    /**
     * Həkimin müəyyən bir klinikada təklif etdiyi xidmətləri qaytarır.
     * @param int $clinicId
     * @return BelongsToMany
     */
    public function clinicServices(int $clinicId): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'doctor_clinic')
            ->wherePivot('clinic_id', $clinicId)
            ->withPivot(['price', 'duration', 'description', 'is_active'])
            ->withTimestamps();
    }

    /**
     * Həkimin müəyyən bir klinikada iş cədvəlini qaytarır.
     * @param int $clinicId
     * @return HasMany
     */
    public function clinicSchedules(int $clinicId): HasMany
    {
        return $this->hasMany(DoctorSchedule::class)->where('clinic_id', $clinicId);
    }

    /**
     * Həkimin aktiv klinikalarını qaytarır.
     * @return BelongsToMany
     */
    public function activeClinics(): BelongsToMany
    {
        return $this->clinics()->wherePivot('is_active', true);
    }

    /**
     * Həkimin əsas iş yerini qaytarır.
     * @return Model
     */
    public function mainWorkplace(): Model
    {
        return $this->clinics()->wherePivot('is_main_workplace', true)->first();
    }

    /**
     * Həkimin bugünkü iş cədvəlini qaytarır.
     * @return HasMany
     */
    public function todaySchedule(): HasMany
    {
        $dayOfWeek = now()->format('l'); // Monday, Tuesday, etc.
        return $this->schedules()->where('day_of_week', $dayOfWeek)->where('is_active', true);
    }

    /**
     * Həkimin bugünkü məşğulluqlarını qaytarır.
     * @return HasMany
     */
    public function todayUnavailabilities(): HasMany
    {
        $today = now()->startOfDay();
        $tomorrow = now()->addDay()->startOfDay();

        return $this->unavailabilities()
            ->where(function($query) use ($today, $tomorrow) {
                $query->whereBetween('start_datetime', [$today, $tomorrow])
                    ->orWhereBetween('end_datetime', [$today, $tomorrow])
                    ->orWhere(function($query) use ($today, $tomorrow) {
                        $query->where('start_datetime', '<', $today)
                            ->where('end_datetime', '>', $tomorrow);
                    });
            });
    }

    /**
     * Həkimin bugünkü boş vaxtlarını qaytarır.
     * @return array
     */
    public function getAvailableTimeSlotsForToday(): array
    {
        // Həkimin bu günkü iş saatları
        $todaySchedules = $this->todaySchedule()->get();
        if ($todaySchedules->isEmpty()) {
            return [];
        }

        // Həkimin bu günkü məşğulluqları
        $todayUnavailabilities = $this->todayUnavailabilities()->get();

        // Həkimin bu günkü randevuları
        $today = now()->startOfDay();
        $tomorrow = now()->addDay()->startOfDay();
        $todayAppointments = $this->appointments()
            ->whereBetween('start_time', [$today, $tomorrow])
            ->where('appointment_status', '!=', 'cancelled')
            ->get();

        $availableTimeSlots = [];

        foreach ($todaySchedules as $schedule) {
            $startTime = now()->setTimeFromTimeString($schedule->start_time);
            $endTime = now()->setTimeFromTimeString($schedule->end_time);

            // Əgər başlama vaxtı keçibsə, başlama vaxtını indiki vaxt olaraq təyin et
            if ($startTime->isPast()) {
                $startTime = now();
            }

            $duration = $schedule->appointment_duration;
            $currentSlot = $startTime->copy();

            while ($currentSlot->addMinutes($duration)->lte($endTime)) {
                $slotStart = $currentSlot->copy();
                $slotEnd = $currentSlot->copy()->addMinutes($duration);

                // Məşğulluqları yoxla
                $isUnavailable = false;
                foreach ($todayUnavailabilities as $unavailability) {
                    if (
                        ($slotStart->between($unavailability->start_datetime, $unavailability->end_datetime)) ||
                        ($slotEnd->between($unavailability->start_datetime, $unavailability->end_datetime)) ||
                        ($unavailability->start_datetime->between($slotStart, $slotEnd)) ||
                        ($unavailability->end_datetime->between($slotStart, $slotEnd))
                    ) {
                        $isUnavailable = true;
                        break;
                    }
                }

                // Randevuları yoxla
                foreach ($todayAppointments as $appointment) {
                    if (
                        ($slotStart->between($appointment->start_time, $appointment->end_time)) ||
                        ($slotEnd->between($appointment->start_time, $appointment->end_time)) ||
                        ($appointment->start_time->between($slotStart, $slotEnd)) ||
                        ($appointment->end_time->between($slotStart, $slotEnd))
                    ) {
                        $isUnavailable = true;
                        break;
                    }
                }

                if (!$isUnavailable) {
                    $availableTimeSlots[] = [
                        'start' => $slotStart->format('H:i'),
                        'end' => $slotEnd->format('H:i'),
                        'clinic_id' => $schedule->clinic_id
                    ];
                }

                $currentSlot = $slotStart;
            }
        }

        return $availableTimeSlots;
    }

    /**
     * Həkimin növbəti həftə üçün iş cədvəlini qaytarır.
     * @return HasMany
     */
    public function nextWeekSchedule(): HasMany
    {
        return $this->schedules()->where('is_active', true);
    }

    /**
     * Həkimin adına görə axtarış.
     * @param Builder $query
     * @param string $name
     * @return Builder
     */
    public function scopeSearchByName(Builder $query, string $name): Builder
    {
        return $query->whereHas('user', function($q) use ($name) {
            $q->where('name', 'like', "%{$name}%")
                ->orWhere('surname', 'like', "%{$name}%");
        });
    }

    /**
     * Həkimin adına görə axtarış.
     * @param Builder $query
     * @return Builder
     */
    public function scopeIsActive(Builder $query): Builder
    {
        return $query->whereHas('user', function($q) {
            $q->where('status', UserStatusEnum::Active);
        });
    }

    /**
     * İxtisasa görə axtarış.
     * @param Builder $query
     * @param int $categoryId
     * @return Builder
     */
    public function scopeByCategory(Builder $query, int $categoryId): Builder
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Alt ixtisasa görə axtarış.
     * @param Builder $query
     * @param int $subCategoryId
     * @return Builder
     */
    public function scopeBySubCategory(Builder $query, int $subCategoryId): Builder
    {
        return $query->where('sub_category_id', $subCategoryId);
    }

    /**
     * Klinikaya görə axtarış.
     * @param Builder $query
     * @param int $clinicId
     * @return Builder
     */
    public function scopeByClinic(Builder $query, int $clinicId): Builder
    {
        return $query->whereHas('clinics', function($q) use ($clinicId) {
            $q->where('clinic_id', $clinicId)
                ->where('is_active', true);
        });
    }

    /**
     * Xidmətə görə axtarış.
     * @param Builder $query
     * @param int $serviceId
     * @return Builder
     */
    public function scopeByService(Builder $query, int $serviceId): Builder
    {
        return $query->whereHas('services', function($q) use ($serviceId) {
            $q->where('service_id', $serviceId)
                ->where('is_active', true);
        });
    }

    /**
     * Ev ziyarəti edən həkimləri qaytarır.
     * @param Builder $query
     * @return Builder
     */
    public function scopeHomeVisit(Builder $query): Builder
    {
        return $query->where('available_for_home_visit', true);
    }

    /**
     * Onlayn konsultasiya edən həkimləri qaytarır.
     * @param Builder $query
     * @return Builder
     */
    public function scopeOnlineConsultation(Builder $query): Builder
    {
        return $query->where('available_for_online_consultation', true);
    }

    /**
     * Təsdiqlənmiş həkimləri qaytarır.
     * @param Builder $query
     * @return Builder
     */
    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('is_verified', true);
    }

    /**
     * Önə çıxarılmış həkimləri qaytarır.
     * @param Builder $query
     * @return Builder
     */
    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    /**
     * Təcrübəyə görə sıralama.
     * @param Builder $query
     * @param string $direction
     * @return Builder
     */
    public function scopeOrderByExperience(Builder $query, string $direction = 'desc'): Builder
    {
        return $query->orderBy('years_of_experience', $direction);
    }

    /**
     * Qiymətləndirməyə görə sıralama.
     * @param Builder $query
     * @param string $direction
     * @return Builder
     */
    public function scopeOrderByRating(Builder $query, string $direction = 'desc'): Builder
    {
        return $query->orderByRaw('average_rating / NULLIF(total_ratings, 0) ' . $direction);
    }

    /**
     * Xəstə sayına görə sıralama.
     * @param Builder $query
     * @param string $direction
     * @return Builder
     */
    public function scopeOrderByPatients(Builder $query, string $direction = 'desc'): Builder
    {
        return $query->orderBy('total_patients', $direction);
    }

    /**
     * Konsultasiya qiymətinə görə sıralama.
     * @param Builder $query
     * @param string $direction
     * @return Builder
     */
    public function scopeOrderByFee(Builder $query, string $direction = 'asc'): Builder
    {
        return $query->orderBy('consultation_fee', $direction);
    }
}
