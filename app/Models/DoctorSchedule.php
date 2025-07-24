<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute as AttributeAlias;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorSchedule extends BaseModel
{
    /**
     * Kütləvi təyin edilə bilən atributlar.
     * @var array
     */
    protected $fillable = [
        'uuid',
        'doctor_id',
        'clinic_id',
        'day_of_week',
        'start_time',
        'end_time',
        'is_active',
        'max_appointments',
        'appointment_duration',
        'note'
    ];

    /**
     * Verilənlər tipini çevrilməli olan atributlar.
     * @var array
     */
    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'is_active' => 'boolean',
        'max_appointments' => 'integer',
        'appointment_duration' => 'integer',
    ];

    /**
     * Avtomatik əlavə edilən atributlar.
     * @var array
     */
    protected $appends = ['day_name', 'time_range', 'duration'];

    /**
     * Günün adını Azərbaycan dilində qaytarır.
     * @return AttributeAlias
     */
    public function dayName(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                return match($this->day_of_week) {
                    'Monday' => 'Bazar ertəsi',
                    'Tuesday' => 'Çərşənbə axşamı',
                    'Wednesday' => 'Çərşənbə',
                    'Thursday' => 'Cümə axşamı',
                    'Friday' => 'Cümə',
                    'Saturday' => 'Şənbə',
                    'Sunday' => 'Bazar',
                    default => $this->day_of_week
                };
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
     * İş saatlarının ümumi müddətini (dəqiqə ilə) qaytarır.
     * @return AttributeAlias
     */
    public function duration(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                return $this->start_time->diffInMinutes($this->end_time);
            }
        );
    }

    /**
     * İş cədvəlinə aid həkim əlaqəsi.
     * @return BelongsTo
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    /**
     * İş cədvəlinə aid klinika əlaqəsi.
     * @return BelongsTo
     */
    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    /**
     * Gün ərzində yaradıla biləcək randevu sayını hesablayır.
     * @return int
     */
    public function calculatePossibleAppointments(): int
    {
        $totalMinutes = $this->duration;
        $appointmentDuration = $this->appointment_duration;

        return (int) floor($totalMinutes / $appointmentDuration);
    }

    /**
     * Gün ərzində mümkün olan randevu vaxtlarını qaytarır.
     * @return array
     */
    public function getPossibleTimeSlots(): array
    {
        $startTime = $this->start_time->copy();
        $endTime = $this->end_time->copy();
        $duration = $this->appointment_duration;

        $timeSlots = [];
        $currentTime = $startTime->copy();

        while ($currentTime->copy()->addMinutes($duration)->lte($endTime)) {
            $timeSlots[] = [
                'start' => $currentTime->format('H:i'),
                'end' => $currentTime->copy()->addMinutes($duration)->format('H:i')
            ];

            $currentTime->addMinutes($duration);
        }

        return $timeSlots;
    }

}
