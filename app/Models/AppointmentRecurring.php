<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute as AttributeAlias;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AppointmentRecurring extends BaseModel
{
    /**
     * İstifadə ediləcək cədvəl adı.
     * @var string
     */
    protected $table = 'appointment_recurring';

    /**
     * Kütləvi təyin edilə bilən atributlar.
     * @var array
     */
    protected $fillable = [
        'uuid',
        'doctor_id',
        'patient_id',
        'clinic_id',
        'service_id',
        'frequency',
        'interval',
        'days_of_week',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'notes',
        'is_active'
    ];

    /**
     * Verilənlər tipini çevrilməli olan atributlar.
     * @var array
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'days_of_week' => 'json',
        'interval' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Avtomatik əlavə edilən atributlar.
     * @var array
     */
    protected $appends = [
        'frequency_text',
        'date_range',
        'time_range',
        'is_expired',
        'duration',
        'status'
    ];

    /**
     * Təkrarlanma tezliyinin mətn təsvirini qaytarır.
     * @return AttributeAlias
     */
    public function frequencyText(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                return match ($this->frequency) {
                    'daily' => 'Gündəlik',
                    'weekly' => 'Həftəlik',
                    'monthly' => 'Aylıq',
                    default => $this->frequency
                };
            }
        );
    }

    /**
     * Tarix aralığını qaytarır.
     * @return AttributeAlias
     */
    public function dateRange(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                $startDate = $this->start_date->format('d.m.Y');
                $endDate = $this->end_date ? $this->end_date->format('d.m.Y') : 'Müddətsiz';

                return $startDate . ' - ' . $endDate;
            }
        );
    }

    /**
     * Saat aralığını qaytarır.
     * @return AttributeAlias
     */
    public function timeRange(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                return $this->start_time->format('H:i') . ' - ' . $this->end_time->format('H:i');
            }
        );
    }

    /**
     * Təkrarlanan randevunun müddətinin bitib-bitmədiyini yoxlayır.
     * @return AttributeAlias
     */
    public function isExpired(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                if (!$this->end_date) {
                    return false;
                }

                return $this->end_date->isPast();
            }
        );
    }

    /**
     * Randevu müddətini (dəqiqə ilə) qaytarır.
     * @return int
     */
    public function getDurationAttribute(): int
    {
        $startTime = Carbon::parse($this->start_time);
        $endTime = Carbon::parse($this->end_time);

        return $startTime->diffInMinutes($endTime);
    }

    /**
     * Təkrarlanan randevunun statusunu qaytarır.
     * @return AttributeAlias
     */
    public function getStatusAttribute(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                if (!$this->is_active) {
                    return 'Deaktiv';
                }

                if ($this->is_expired) {
                    return 'Bitmiş';
                }

                if ($this->start_date->isFuture()) {
                    return 'Gələcək';
                }

                return 'Aktiv';
            }
        );
    }

    /**
     * Təkrarlanan randevunun təsvirini qaytarır.
     * @return AttributeAlias
     */
    public function description(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                $description = $this->frequency_text;

                if ($this->interval > 1) {
                    $description .= ' (hər ' . $this->interval . ' ';

                    $description .= match($this->frequency) {
                        'daily' => 'gün',
                        'weekly' => 'həftə',
                        'monthly' => 'ay',
                        default => ''
                    };

                    $description .= ')';
                }

                if ($this->frequency === 'weekly' && $this->days_of_week) {
                    $dayNames = [];
                    $daysOfWeek = (array) $this->days_of_week;

                    $dayTranslations = [
                        'monday' => 'Bazar ertəsi',
                        'tuesday' => 'Çərşənbə axşamı',
                        'wednesday' => 'Çərşənbə',
                        'thursday' => 'Cümə axşamı',
                        'friday' => 'Cümə',
                        'saturday' => 'Şənbə',
                        'sunday' => 'Bazar'
                    ];

                    foreach ($daysOfWeek as $day) {
                        $dayNames[] = $dayTranslations[strtolower($day)] ?? $day;
                    }

                    $description .= ': ' . implode(', ', $dayNames);
                }

                return $description;
            }
        );
    }

    /**
     * Təkrarlanan randevuya aid həkim əlaqəsi.
     * @return BelongsTo
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    /**
     * Təkrarlanan randevuya aid xəstə əlaqəsi.
     * @return BelongsTo
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Təkrarlanan randevuya aid klinika əlaqəsi.
     * @return BelongsTo
     */
    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    /**
     * Təkrarlanan randevuya aid xidmət əlaqəsi.
     * @return BelongsTo
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Təkrarlanan randevudan yaranan randevular əlaqəsi.
     * @return HasMany
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'recurring_id');
    }

    /**
     * Təkrarlanan randevunu deaktiv edir.
     * @return bool
     */
    public function deactivate(): bool
    {
        $this->is_active = false;
        return $this->save();
    }

    /**
     * Təkrarlanan randevunu aktiv edir.
     * @return bool
     */
    public function activate(): bool
    {
        $this->is_active = true;
        return $this->save();
    }

    /**
     * Aktiv təkrarlanan randevuları qaytarır.
     * @param Builder $query
     * @return Builder
     */
    public function scopeActive($query): Builder
    {
        return $query->where('is_active', true)
            ->where(function($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', now()->toDateString());
            });
    }

    /**
     * Bitmiş təkrarlanan randevuları qaytarır.
     * @param Builder $query
     * @return Builder
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->whereNotNull('end_date')
            ->where('end_date', '<', now()->toDateString());
    }

    /**
     * Deaktiv təkrarlanan randevuları qaytarır.
     * @param Builder $query
     * @return Builder
     */
    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('is_active', false);
    }

    /**
     * Gündəlik təkrarlanan randevuları qaytarır.
     * @param Builder $query
     * @return Builder
     */
    public function scopeDaily(Builder $query): Builder
    {
        return $query->where('frequency', 'daily');
    }

    /**
     * Həftəlik təkrarlanan randevuları qaytarır.
     * @param Builder $query
     * @return Builder
     */
    public function scopeWeekly(Builder $query): Builder
    {
        return $query->where('frequency', 'weekly');
    }

    /**
     * Aylıq təkrarlanan randevuları qaytarır.
     * @param Builder $query
     * @return Builder
     */
    public function scopeMonthly(Builder $query): Builder
    {
        return $query->where('frequency', 'monthly');
    }

    /**
     * Müəyyən bir günü əhatə edən təkrarlanan randevuları qaytarır.
     * @param Builder $query
     * @param Carbon $date
     * @return Builder
     */
    public function scopeForDate(Builder $query, Carbon $date): Builder
    {
        return $query->where('is_active', true)
            ->where('start_date', '<=', $date->toDateString())
            ->where(function($q) use ($date) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', $date->toDateString());
            });
    }

    /**
     * Növbəti təkrarlanma tarixini hesablayır.
     * @param Carbon|null $lastDate Son təkrarlanma tarixi
     * @return Carbon|null Növbəti təkrarlanma tarixi
     */
    public function calculateNextOccurrence(Carbon $lastDate = null): ?Carbon
    {
        if (!$this->is_active) {
            return null;
        }

        $lastDate = $lastDate ?? $this->start_date;

        switch ($this->frequency) {
            case 'daily':
                $nextDate = $lastDate->copy()->addDays($this->interval);
                break;

            case 'weekly':
                if (empty($this->days_of_week)) {
                    $nextDate = $lastDate->copy()->addWeeks($this->interval);
                } else {
                    // Həftənin günlərini yoxla
                    $nextDate = $lastDate->copy()->addDay();
                    $daysOfWeek = (array) $this->days_of_week;

                    // Ən yaxın uyğun günü tap
                    $found = false;
                    $dayCount = 0;

                    while (!$found && $dayCount < 7 * $this->interval) {
                        $dayName = strtolower($nextDate->format('l')); // monday, tuesday, ...

                        if (in_array($dayName, array_map('strtolower', $daysOfWeek))) {
                            $found = true;
                        } else {
                            $nextDate->addDay();
                            $dayCount++;
                        }
                    }

                    if (!$found) {
                        return null;
                    }
                }
                break;

            case 'monthly':
                $nextDate = $lastDate->copy()->addMonths($this->interval);
                break;

            default:
                return null;
        }

        // Son tarixi yoxla
        if ($this->end_date && $nextDate->gt($this->end_date)) {
            return null;
        }

        return $nextDate;
    }

    /**
     * Növbəti təkrarlanma tarixlərini qaytarır.
     * @param int $limit Qaytarılacaq tarix sayı
     * @return array Növbəti tarixlər
     */
    public function getNextOccurrences(int $limit = 10): array
    {
        if (!$this->is_active) {
            return [];
        }

        $occurrences = [];
        $date = $this->start_date->copy();

        while (count($occurrences) < $limit) {
            $nextDate = $this->calculateNextOccurrence($date);

            if (!$nextDate) {
                break;
            }

            $occurrences[] = $nextDate->format('Y-m-d');
            $date = $nextDate;
        }

        return $occurrences;
    }

    /**
     * Növbəti randevular üçün vaxt aralıqlarını yaradır.
     * @param int $limit Yaradılacaq randevu sayı
     * @return array Randevu məlumatları
     */
    public function generateAppointmentSlots(int $limit = 5): array
    {
        $dates = $this->getNextOccurrences($limit);
        $slots = [];

        foreach ($dates as $date) {
            $startTime = Carbon::parse($date . ' ' . $this->start_time->format('H:i:s'));
            $endTime = Carbon::parse($date . ' ' . $this->end_time->format('H:i:s'));

            $slots[] = [
                'start_time' => $startTime,
                'end_time' => $endTime,
                'doctor_id' => $this->doctor_id,
                'patient_id' => $this->patient_id,
                'clinic_id' => $this->clinic_id,
                'service_id' => $this->service_id,
                'recurring_id' => $this->id,
                'notes' => $this->notes
            ];
        }

        return $slots;
    }
}
