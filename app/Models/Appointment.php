<?php

namespace App\Models;

use App\Enums\AppointmentServiceEnum;
use App\Traits\Model\HasCode;
use App\Traits\Model\HasUuid;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Appointment extends Model
{
    use HasUuid, HasCode, SoftDeletes;

    protected $fillable = [
        'uuid',
        'appointment_number',
        'doctor_id',
        'patient_id',
        'service_id',
        'clinic_id',
        'availability_id',
        'status',
        'appointment_date',
        'start_time',
        'end_time',
        'reason',
        'symptoms',
        'notes',
        'diagnosis',
        'treatment',
        'prescription',
        'doctor_notes',
        'fee',
        'is_paid',
        'paid_at',
        'payment_method',
        'payment_reference',
        'confirmed_at',
        'cancelled_at',
        'cancellation_reason',
        'completed_at',
        'meta_data',
        'created_by'
    ];

    protected $casts = [
        'appointment_date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'fee' => 'decimal:2',
        'is_paid' => 'boolean',
        'paid_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'completed_at' => 'datetime',
        'meta_data' => 'json'
    ];

    protected $appends = ['formatted_time', 'duration', 'can_cancel'];

    /**
     * Model yaradıldıqda avtomatik randevu nömrəsi yaradır
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->appointment_number) {
                $model->appointment_number = static::generateAppointmentNumber();
            }
        });
    }

    /**
     * Randevu nömrəsi yaradır
     */
    public static function generateAppointmentNumber(): string
    {
        $prefix = 'APT-' . date('Ymd');
        $lastAppointment = static::where('appointment_number', 'like', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();

        if ($lastAppointment) {
            $lastNumber = (int) substr($lastAppointment->appointment_number, -3);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . '-' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }

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
     * Randevunun müddətini dəqiqələrlə qaytarır
     */
    protected function duration(): Attribute
    {
        return Attribute::make(
            get: function () {
                $start = Carbon::createFromFormat('H:i', $this->start_time);
                $end = Carbon::createFromFormat('H:i', $this->end_time);

                return $start->diffInMinutes($end);
            }
        );
    }

    /**
     * Randevunun ləğv edilə biləcəyini yoxlayır
     */
    protected function canCancel(): Attribute
    {
        return Attribute::make(
            get: function () {
                // Əgər randevu artıq ləğv edilib, tamamlanıb və ya pasient gəlməyibsə
                if ($this->is_cancelled || $this->is_completed || $this->is_no_show) {
                    return false;
                }

                // Əgər randevuya 24 saatdan az qalıbsa
                $appointmentDateTime = Carbon::createFromFormat(
                    'Y-m-d H:i',
                    $this->appointment_date->format('Y-m-d') . ' ' . $this->start_time->format('H:i')
                );

                if (Carbon::now()->diffInHours($appointmentDateTime) < 24) {
                    return false;
                }

                return true;
            }
        );
    }

    /**
     * Həkim əlaqəsi
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    /**
     * Pasient əlaqəsi
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    /**
     * Xidmət əlaqəsi
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    /**
     * Klinika əlaqəsi
     */
    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    /**
     * Boş vaxt əlaqəsi
     */
    public function availability(): BelongsTo
    {
        return $this->belongsTo(DoctorAvailability::class, 'availability_id');
    }

    /**
     * Yaradıcı əlaqəsi
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Tarixçə əlaqəsi
     */
    public function history(): HasMany
    {
        return $this->hasMany(AppointmentHistory::class)->orderBy('created_at', 'desc');
    }

    /**
     * Xatırlatmalar əlaqəsi
     */
    public function reminders(): HasMany
    {
        return $this->hasMany(AppointmentReminder::class);
    }

    /**
     * Randevu rəyləri
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(ClinicReview::class);
    }

    /**
     * Statusu təsdiqlənmiş olaraq işarələyir
     */
    public function confirm(User $user = null): bool
    {
        // Əvvəlcə boş vaxtı məşğul kimi işarələyək
        if ($this->availability) {
            $this->availability->markAsBusy('Appointment Confirmed');
        }

        // Statusu yeniləyək
        $confirmedStatus = AppointmentServiceEnum::Confirmed;

        $updated = $this->update([
            'status' => $confirmedStatus,
            'confirmed_at' => now()
        ]);

        if ($updated) {
            // Tarixçəyə əlavə edək
            $this->addHistory('confirm', $confirmedStatus, $user, 'Randevu təsdiqləndi');

            // Xatırlatmaları planlaşdıraq
            $this->scheduleReminders();
        }

        return $updated;
    }

    /**
     * Statusu ləğv edilmiş olaraq işarələyir
     */
    public function cancel(string $reason = null, User $user = null): bool
    {
        // Əvvəlcə boş vaxtı yenidən mövcud kimi işarələyək
        if ($this->availability) {
            $this->availability->markAsAvailable();
        }

        // Statusu yeniləyək
        $cancelledStatus = AppointmentServiceEnum::Cancelled;

        $updated = $this->update([
            'status' => $cancelledStatus,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason
        ]);

        if ($updated) {
            // Tarixçəyə əlavə edək
            $this->addHistory(
                'cancel',
                $cancelledStatus,
                $user,
                'Randevu ləğv edildi' . ($reason ? ": {$reason}" : '')
            );
        }

        return $updated;
    }

    /**
     * Statusu tamamlanmış olaraq işarələyir
     */
    public function complete(User $user = null): bool
    {
        // Statusu yeniləyək
        $completedStatus = AppointmentServiceEnum::Completed;

        $updated = $this->update([
            'status' => $completedStatus,
            'completed_at' => now()
        ]);

        if ($updated) {
            // Tarixçəyə əlavə edək
            $this->addHistory('complete', $completedStatus, $user, 'Randevu tamamlandı');
        }

        return $updated;
    }

    /**
     * Statusu pasient gəlmədi olaraq işarələyir
     */
    public function markAsNoShow(User $user = null): bool
    {
        // Statusu yeniləyək
        $noShowStatus = AppointmentServiceEnum::NoShow;

        $updated = $this->update([
            'status' => $noShowStatus
        ]);

        if ($updated) {
            // Tarixçəyə əlavə edək
            $this->addHistory('no_show', $noShowStatus, $user, 'Pasient gəlmədi');
        }

        return $updated;
    }

    /**
     * Randevu tarixçəsinə yeni qeyd əlavə edir
     */
    public function addHistory(string $action, int $status, ?User $user = null, ?string $notes = null, ?array $changes = null): AppointmentHistory
    {
        return AppointmentHistory::create([
            'appointment_id' => $this->id,
            'status' => $status,
            'user_id' => $user?->id,
            'action' => $action,
            'notes' => $notes,
            'changes' => $changes
        ]);
    }

    /**
     * Randevu xatırlatmalarını planlaşdırır
     */
    protected function scheduleReminders(): void
    {
        // Artıq planlaşdırılmış xatırlatmaları siləm
        $this->reminders()->delete();

        $appointmentDateTime = Carbon::createFromFormat(
            'Y-m-d H:i',
            $this->appointment_date->format('Y-m-d') . ' ' . $this->start_time->format('H:i')
        );

        // 24 saat əvvəl xatırlatma
        AppointmentReminder::create([
            'appointment_id' => $this->id,
            'type' => 'email',
            'scheduled_at' => $appointmentDateTime->copy()->subHours(24),
            'message' => "Sabah saat {$this->start_time->format('H:i')}-da {$this->doctor->fullname} ilə randevunuz var."
        ]);

        // 1 saat əvvəl xatırlatma
        AppointmentReminder::create([
            'appointment_id' => $this->id,
            'type' => 'email',
            'scheduled_at' => $appointmentDateTime->copy()->subHours(1),
            'message' => "1 saat sonra saat {$this->start_time->format('H:i')}-da {$this->doctor->fullname} ilə randevunuz var."
        ]);
    }
}
