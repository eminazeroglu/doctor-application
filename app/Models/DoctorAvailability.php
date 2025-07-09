<?php

namespace App\Models;

use App\Enums\AppointmentServiceEnum;
use App\Traits\Model\HasUuid;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DoctorAvailability extends Model
{
    use HasUuid;

    protected $fillable = [
        'uuid',
        'user_id',
        'clinic_id',
        'schedule_id',
        'date',
        'start_time',
        'end_time',
        'status',
        'reason',
        'meta_data'
    ];

    protected $casts = [
        'date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'meta_data' => 'json'
    ];

    protected $appends = ['formatted_time', 'is_booked', 'duration_in_minutes'];

    /**
     * Vaxtı formatlı şəkildə qaytarır
     */
    protected function formattedTime(): Attribute
    {
        return Attribute::make(
            get: function () {
                return "{$this->start_time->format('H:i')} - {$this->end_time->format('H:i')}";
            }
        );
    }

    /**
     * Boş vaxtın rezervasiya olunub-olunmadığını yoxlayır
     */
    protected function isBooked(): Attribute
    {
        return Attribute::make(
            get: function () {
                return $this->appointments()
                    ->whereIn('status', [AppointmentServiceEnum::Pending, AppointmentServiceEnum::Confirmed])
                    ->exists();
            }
        );
    }

    /**
     * Bu boş vaxtın sahibi olan həkim
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Bu boş vaxtın aid olduğu klinika
     */
    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    /**
     * Bu boş vaxtın yaradıldığı cədvəl
     */
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(DoctorSchedule::class, 'schedule_id');
    }

    /**
     * Bu boş vaxt üçün yaradılmış randevular
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'availability_id');
    }

    /**
     * Vaxtın müddətini dəqiqələrlə qaytarır
     */

    public function durationInMinutes(): Attribute
    {
        return new Attribute(
            get: function () {
                $start = Carbon::createFromFormat('H:i', $this->start_time);
                $end = Carbon::createFromFormat('H:i', $this->end_time);

                return $start->diffInMinutes($end);
            }
        );
    }

    /**
     * Vaxtın mövcud olub-olmadığını yoxlayır
     */
    public function isAvailable(): bool
    {
        if ($this->status !== 'available') {
            return false;
        }

        // Əgər vaxt artıq keçibsə
        if ($this->date->isPast() ||
            ($this->date->isToday() && Carbon::now()->greaterThan($this->start_time))) {
            return false;
        }

        // Əgər randevu varsa
        return !$this->is_booked;
    }

    /**
     * Statusu "məşğul" olaraq işarələyir
     */
    public function markAsBusy(string $reason = null): bool
    {
        return $this->update([
            'status' => 'busy',
            'reason' => $reason
        ]);
    }

    /**
     * Statusu "mövcud deyil" olaraq işarələyir
     */
    public function markAsUnavailable(string $reason = null): bool
    {
        return $this->update([
            'status' => 'unavailable',
            'reason' => $reason
        ]);
    }

    /**
     * Statusu "mövcuddur" olaraq işarələyir
     */
    public function markAsAvailable(): bool
    {
        return $this->update([
            'status' => 'available',
            'reason' => null
        ]);
    }
}
