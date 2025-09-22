<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute as AttributeAlias;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorSchedule extends BaseModel
{
    use HasFactory;

    protected $table = 'doctor_schedules';

    /**
     * Mass-assignable sahələr
     */
    protected $fillable = [
        'uuid',
        'doctor_id',
        'clinic_id',

        // Recurring availability
        'start_date',     // Y-m-d
        'end_date',       // Y-m-d
        'from_time',      // H:i
        'to_time',        // H:i
        'frequency',      // daily|weekly|monthly
        'every',          // 1,2,3...
        'days',           // json: [0..6]

        'is_active',
        'note',
    ];

    /**
     * Type cast-lar
     */
    protected $casts = [
        'start_date' => 'date:Y-m-d',
        'end_date'   => 'date:Y-m-d',
        'from_time'  => 'datetime:H:i:s',
        'to_time'    => 'datetime:H:i:s',
        'every'      => 'integer',
        'days'       => 'array',
        'is_active'  => 'boolean',
    ];

    /**
     * Hesablanmış sahələr
     */
    protected $appends = [
        'time_range',          // "HH:mm - HH:mm"
        'duration_minutes',    // int (dəqiqə)
    ];

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    /**
     * HH:mm - HH:mm formatında vaxt aralığı
     */
    public function timeRange(): AttributeAlias
    {
        return new AttributeAlias(
            get: fn () => sprintf('%s - %s', $this->from_time, $this->to_time)
        );
    }

    /**
     * from_time → to_time aralığının dəqiqə ilə uzunluğu
     */
    public function durationMinutes(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                if (!$this->from_time || !$this->to_time) {
                    return 0;
                }

                try {
                    $start = Carbon::parse($this->from_time);
                    $end   = Carbon::parse($this->to_time);
                    return $start->diffInMinutes($end);
                } catch (\Exception $e) {
                    return 0;
                }
            }
        );
    }

    public function formatedFromTime(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                return $this->from_time?->format('H:i');
            }
        );
    }

    public function formatedToTime(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                return $this->to_time?->format('H:i');
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Yalnız aktiv cədvəllər
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Müəyyən həkim üçün
     */
    public function scopeForDoctor($query, int $doctorId)
    {
        return $query->where('doctor_id', $doctorId);
    }

    /**
     * Verilmiş tarix aralığı ilə kəsişən cədvəllər
     */
    public function scopeIntersectsDateRange($query, string $from, string $to)
    {
        return $query->where(function ($q) use ($from, $to) {
            $q->whereBetween('start_date', [$from, $to])
                ->orWhereBetween('end_date',   [$from, $to])
                ->orWhere(function ($q2) use ($from, $to) {
                    $q2->where('start_date', '<=', $from)
                        ->where('end_date',   '>=', $to);
                });
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Weekly üçün “every N week” addımının uyğunluğunu yoxlayır
     * (Service tərəfdə də eynisi var – lazım olsa UI üçün də yararlıdır)
     */
    public function matchesWeekStep(Carbon $candidate): bool
    {
        if ($this->frequency !== 'weekly') {
            return true;
        }

        $start = Carbon::parse($this->start_date)->startOfWeek();
        $diff  = $start->diffInWeeks($candidate->copy()->startOfWeek());
        $every = max((int)$this->every, 1);

        return $diff % $every === 0;
    }

    /**
     * Weekly üçün gün uyğunluğunu yoxlayır (0..6; 0=Sun)
     */
    public function matchesWeekDay(Carbon $candidate): bool
    {
        if ($this->frequency !== 'weekly') {
            return true;
        }

        $days = is_array($this->days) ? $this->days : [];
        return in_array((int)$candidate->dayOfWeek, $days, true);
    }
}
