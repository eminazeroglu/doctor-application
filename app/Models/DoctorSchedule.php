<?php

namespace App\Models;

use App\Traits\Model\HasUuid;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DoctorSchedule extends Model
{
    use HasUuid, SoftDeletes;

    protected $fillable = [
        'uuid',
        'user_id',
        'clinic_id',
        'type',
        'start_date',
        'end_date',
        'frequency',
        'interval',
        'days_of_week',
        'start_time',
        'end_time',
        'slot_duration',
        'break_time',
        'meta_data',
        'is_active'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'days_of_week' => 'json',
        'meta_data' => 'json',
        'is_active' => 'boolean'
    ];

    protected $appends = ['display_name', 'formatted_days'];

    /**
     * Cədvəlin adını qaytarır
     */
    protected function displayName(): Attribute
    {
        return Attribute::make(
            get: function () {
                $doctor = $this->doctor->fullname;
                $clinic = $this->clinic->name;

                if ($this->type === 'one_time') {
                    return "{$doctor} - {$clinic} - {$this->start_date->format('d.m.Y')}";
                }

                return "{$doctor} - {$clinic} - {$this->frequency}";
            }
        );
    }

    /**
     * Həftənin günlərini formatlı şəkildə qaytarır
     */
    protected function formattedDays(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->days_of_week || $this->type !== 'recurring') {
                    return null;
                }

                $dayNames = [
                    1 => 'B.e',
                    2 => 'Ç.a',
                    3 => 'Çər',
                    4 => 'C.a',
                    5 => 'Cüm',
                    6 => 'Şən',
                    7 => 'Baz'
                ];

                $days = [];
                foreach ($this->days_of_week as $day) {
                    $days[] = $dayNames[$day] ?? "Gün {$day}";
                }

                return implode(', ', $days);
            }
        );
    }

    /**
     * Bu cədvəlin sahibi olan həkim
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Bu cədvəlin aid olduğu klinika
     */
    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    /**
     * Bu cədvəldən yaradılan boş vaxtlar
     */
    public function availabilities(): HasMany
    {
        return $this->hasMany(DoctorAvailability::class, 'schedule_id');
    }

    /**
     * Verilən tarix aralığı üçün boş vaxtları yaradır
     */
    public function generateAvailabilities(Carbon $startDate, Carbon $endDate): array
    {
        $generatedCount = 0;
        $skippedCount = 0;
        $currentDate = clone $startDate;

        // Birdəfəlik cədvəl üçün
        if ($this->type === 'one_time') {
            // Əgər cədvəl tarixi verilən aralıqda deyilsə, heç nə yaratmırıq
            if (!$this->start_date->between($startDate, $endDate)) {
                return ['generated' => 0, 'skipped' => 0];
            }

            // Boş vaxtları yaradaq
            $generated = $this->createAvailabilitiesForDate($this->start_date);
            return ['generated' => $generated, 'skipped' => 0];
        }

        // Təkrarlanan cədvəl üçün
        while ($currentDate->lte($endDate)) {
            // Əgər cədvəlin son tarixi varsa və cari tarix bundan sonradırsa, dayandırırıq
            if ($this->end_date && $currentDate->gt($this->end_date)) {
                break;
            }

            // Əgər cari tarix cədvəlin başlama tarixindən əvvəldirsə, növbəti günə keçirik
            if ($currentDate->lt($this->start_date)) {
                $currentDate->addDay();
                continue;
            }

            // Həftəlik təkrarlanma üçün, həftənin günü uyğun gəlirmi
            if ($this->frequency === 'weekly' && !$this->isDayOfWeekIncluded($currentDate->dayOfWeek)) {
                $currentDate->addDay();
                continue;
            }

            // Interval yoxlaması - məsələn, hər 2 həftə/ay
            if (!$this->isDateInInterval($currentDate)) {
                $currentDate->addDay();
                continue;
            }

            // Bu tarix üçün boş vaxtlar yaradılıbmı
            $exists = $this->availabilities()
                ->where('date', $currentDate->format('Y-m-d'))
                ->exists();

            if ($exists) {
                $skippedCount++;
            } else {
                $generatedCount += $this->createAvailabilitiesForDate($currentDate);
            }

            $currentDate->addDay();
        }

        return ['generated' => $generatedCount, 'skipped' => $skippedCount];
    }

    /**
     * Verilən tarix üçün boş vaxtları yaradır və yaradılan boş vaxtların sayını qaytarır
     */
    protected function createAvailabilitiesForDate(Carbon $date): int
    {
        $count = 0;
        $startTime = Carbon::createFromFormat('H:i:s', $this->start_time);
        $endTime = Carbon::createFromFormat('H:i:s', $this->end_time);

        // Saniyələri sıfırlayaq
        $startTime->second(0);
        $endTime->second(0);

        while ($startTime->copy()->addMinutes($this->slot_duration)->lte($endTime)) {
            // Boş vaxt yaradaq
            DoctorAvailability::create([
                'user_id' => $this->user_id,
                'clinic_id' => $this->clinic_id,
                'schedule_id' => $this->id,
                'date' => $date->format('Y-m-d'),
                'start_time' => $startTime->format('H:i'),
                'end_time' => $startTime->copy()->addMinutes($this->slot_duration)->format('H:i'),
                'status' => 'available'
            ]);

            $count++;

            // Növbəti slota keçid
            $startTime->addMinutes($this->slot_duration + $this->break_time);
        }

        return $count;
    }

    /**
     * Həftənin günü cədvələ daxildirmi
     */
    protected function isDayOfWeekIncluded(int $dayOfWeek): bool
    {
        // Carbon 0-6 (Bazar-Şənbə) istifadə edir, bizim model isə 1-7 (Bazar ertəsi-Bazar)
        $day = $dayOfWeek === 0 ? 7 : $dayOfWeek;
        return in_array($day, $this->days_of_week ?? []);
    }

    /**
     * Tarix interval-a uyğun gəlirmi
     */
    protected function isDateInInterval(Carbon $date): bool
    {
        if ($this->interval <= 1) {
            return true;
        }

        $startDate = $this->start_date;

        if ($this->frequency === 'weekly') {
            $weeksDiff = $startDate->diffInWeeks($date);
            return $weeksDiff % $this->interval === 0;
        }

        if ($this->frequency === 'monthly') {
            $monthsDiff = $startDate->diffInMonths($date);
            return $monthsDiff % $this->interval === 0;
        }

        return true;
    }
}
