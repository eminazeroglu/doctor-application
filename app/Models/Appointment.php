<?php

namespace App\Models;

use App\Enums\AppointmentStatusEnum;
use DateTime;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute as AttributeAlias;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Appointment extends BaseModel
{
    use SoftDeletes;

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
        'appointment_status_id',
        'start_time',
        'end_time',
        'complaint',
        'notes',
        'price',
        'is_paid',
        'payment_id',
        'cancel_reason',
        'location',
        'consultation_type',
        'additional_info'
    ];

    /**
     * Verilənlər tipini çevrilməli olan atributlar.
     * @var array
     */
    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'is_paid' => 'boolean',
        'price' => 'float',
        'additional_info' => 'json',
    ];

    /**
     * Avtomatik əlavə edilən atributlar.
     * @var array
     */
    protected $appends = ['duration', 'is_completed', 'is_active', 'is_upcoming', 'status_text', 'status_color', 'status_icon'];

    /**
     * Randevunun müddətini (dəqiqə ilə) qaytarır.
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
     * Randevunun bitdiyini yoxlayır.
     * @return AttributeAlias
     */
    public function isCompleted(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                return $this->end_time->isPast();
            }
        );
    }

    /**
     * Randevunun aktiv olduğunu yoxlayır.
     * @return AttributeAlias
     */
    public function isActive(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                return now()->between($this->start_time, $this->end_time);
            }
        );
    }

    /**
     * Randevunun gələcəkdə olduğunu yoxlayır.
     * @return AttributeAlias
     */
    public function isUpcoming(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                return $this->start_time->isFuture();
            }
        );
    }

    /**
     * Randevu statusunun mətn təsvirini qaytarır.
     * @return AttributeAlias
     */
    public function statusText(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                return AppointmentStatusEnum::getDescription($this->appointment_status);
            }
        );
    }

    /**
     * Randevu statusunun rəngini qaytarır.
     * @return AttributeAlias
     */
    public function statusColor(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                return AppointmentStatusEnum::getColor($this->appointment_status);
            }
        );
    }

    /**
     * Randevu statusunun ikonunu qaytarır.
     * @return AttributeAlias
     */
    public function statusIcon(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                return AppointmentStatusEnum::getIcon($this->appointment_status);
            }
        );
    }

    /**
     * Randevunun həkimi əlaqəsi.
     * @return BelongsTo
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    /**
     * Randevunun xəstəsi əlaqəsi.
     * @return BelongsTo
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Randevunun klinikası əlaqəsi.
     * @return BelongsTo
     */
    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    /**
     * Randevunun xidməti əlaqəsi.
     * @return BelongsTo
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Randevunun ödənişi əlaqəsi.
     * @return BelongsTo
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Randevunun xatırlatmaları əlaqəsi.
     * @return HasMany
     */
    public function reminders(): HasMany
    {
        return $this->hasMany(AppointmentReminder::class);
    }

    /**
     * Randevunun tibbi qeydləri əlaqəsi.
     * @return HasMany
     */
    public function medicalRecords(): HasMany
    {
        return $this->hasMany(PatientMedicalRecord::class);
    }

    /**
     * Randevunun sənədləri əlaqəsi.
     * @return HasMany
     */
    public function documents(): HasMany
    {
        return $this->hasMany(PatientDocument::class);
    }

    /**
     * Randevunun rəyləri əlaqəsi.
     * @return HasMany
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Randevunun təkrarlanan randevu əlaqəsi.
     * @return BelongsTo
     */
    public function recurringAppointment(): BelongsTo
    {
        return $this->belongsTo(AppointmentRecurring::class, 'recurring_id');
    }

    /**
     * Randevunun statusunu güncəlləyir.
     * @param AppointmentStatusEnum $status
     * @param string|null $reason
     * @return bool
     */
    public function updateStatus(AppointmentStatusEnum $status, ?string $reason = null): bool
    {
        $this->appointment_status = $status;

        if ($status === AppointmentStatusEnum::Cancelled && $reason) {
            $this->cancel_reason = $reason;
        }

        return $this->save();
    }

    /**
     * Randevunu təsdiqləyir.
     * @return bool
     */
    public function confirm(): bool
    {
        $this->appointment_status = AppointmentStatusEnum::Confirmed;
        return $this->save();
    }

    /**
     * Randevunu tamamlanmış kimi işarələyir.
     * @return bool
     */
    public function complete(): bool
    {
        $this->appointment_status = AppointmentStatusEnum::Completed;
        return $this->save();
    }

    /**
     * Randevunu ləğv edir.
     * @param string $reason Ləğv səbəbi
     * @return bool
     */
    public function cancel(string $reason): bool
    {
        $this->appointment_status = AppointmentStatusEnum::Cancelled;
        $this->cancel_reason = $reason;
        return $this->save();
    }

    /**
     * Xəstənin gəlmədiyini qeyd edir.
     * @return bool
     */
    public function markAsNoShow(): bool
    {
        $this->appointment_status = AppointmentStatusEnum::NoShow;
        return $this->save();
    }

    /**
     * Randevunu yenidən planlaşdırılmış kimi qeyd edir.
     * @return bool
     */
    public function markAsRescheduled(): bool
    {
        $this->appointment_status = AppointmentStatusEnum::Rescheduled;
        return $this->save();
    }

    /**
     * Randevunun vaxtını dəyişdirir.
     * @param DateTime $startTime Yeni başlama vaxtı
     * @param DateTime $endTime Yeni bitmə vaxtı
     * @return bool
     */
    public function reschedule(DateTime $startTime, DateTime $endTime): bool
    {
        $this->start_time = $startTime;
        $this->end_time = $endTime;
        $this->appointment_status = AppointmentStatusEnum::Rescheduled;
        return $this->save();
    }

    /**
     * Randevunun ödəndiyini işarələyir.
     * @param int $paymentId Ödəniş ID-si
     * @return bool
     */
    public function markAsPaid(int $paymentId): bool
    {
        $this->is_paid = true;
        $this->payment_id = $paymentId;
        return $this->save();
    }

    /**
     * Randevu üçün xatırlatma yaradır.
     * @param string $type Xatırlatma növü (email, sms, app)
     * @param DateTime $sendAt Göndərmə vaxtı
     * @return AppointmentReminder
     */
    public function createReminder(string $type, DateTime $sendAt): AppointmentReminder
    {
        return $this->reminders()->create([
            'type' => $type,
            'send_at' => $sendAt,
            'is_sent' => false
        ]);
    }

    /**
     * Gözləmədə olan randevuları qaytarır.
     * @param Builder $query
     * @return Builder
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('appointment_status', AppointmentStatusEnum::Pending);
    }

    /**
     * Təsdiqlənmiş randevuları qaytarır.
     * @param Builder $query
     * @return Builder
     */
    public function scopeConfirmed(Builder $query): Builder
    {
        return $query->where('appointment_status', AppointmentStatusEnum::Confirmed);
    }

    /**
     * Tamamlanmış randevuları qaytarır.
     * @param Builder $query
     * @return Builder
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('appointment_status', AppointmentStatusEnum::Completed);
    }

    /**
     * Ləğv edilmiş randevuları qaytarır.
     * @param Builder $query
     * @return Builder
     */
    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('appointment_status', AppointmentStatusEnum::Cancelled);
    }

    /**
     * Xəstənin gəlmədiyi randevuları qaytarır.
     * @param Builder $query
     * @return Builder
     */
    public function scopeNoShow(Builder $query): Builder
    {
        return $query->where('appointment_status', AppointmentStatusEnum::NoShow);
    }

    /**
     * Yenidən planlaşdırılmış randevuları qaytarır.
     * @param Builder $query
     * @return Builder
     */
    public function scopeRescheduled(Builder $query): Builder
    {
        return $query->where('appointment_status', AppointmentStatusEnum::Rescheduled);
    }

    /**
     * Aktiv randevuları qaytarır (Pending, Confirmed, Rescheduled).
     * @param Builder $query
     * @return Builder
     */
    public function scopeActive($query): Builder
    {
        return $query->whereIn('appointment_status', [
            AppointmentStatusEnum::Pending,
            AppointmentStatusEnum::Confirmed,
            AppointmentStatusEnum::Rescheduled
        ]);
    }

    /**
     * Gələcək randevuları qaytarır.
     * @param Builder $query
     * @return Builder
     */
    public function scopeUpcoming($query)
    {
        return $query->where('start_time', '>', now())
            ->whereIn('appointment_status', [
                AppointmentStatusEnum::Pending,
                AppointmentStatusEnum::Confirmed,
                AppointmentStatusEnum::Rescheduled
            ]);
    }

    /**
     * Keçmiş randevuları qaytarır.
     * @param Builder $query
     * @return Builder
     */
    public function scopePast($query): Builder
    {
        return $query->where('end_time', '<', now());
    }

    /**
     * Bu gün olan randevuları qaytarır.
     * @param Builder $query
     * @return Builder
     */
    public function scopeToday($query): Builder
    {
        return $query->whereDate('start_time', now()->toDateString());
    }

    /**
     * Bu həftə olan randevuları qaytarır.
     * @param Builder $query
     * @return Builder
     */
    public function scopeThisWeek($query): Builder
    {
        return $query->whereBetween('start_time', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    /**
     * Bu ay olan randevuları qaytarır.
     * @param Builder $query
     * @return Builder
     */
    public function scopeThisMonth($query): Builder
    {
        return $query->whereBetween('start_time', [
            now()->startOfMonth(),
            now()->endOfMonth()
        ]);
    }

    /**
     * Ödənilmiş randevuları qaytarır.
     * @param Builder $query
     * @return Builder
     */
    public function scopePaid($query): Builder
    {
        return $query->where('is_paid', true);
    }

    /**
     * Ödənilməmiş randevuları qaytarır.
     * @param Builder $query
     * @return Builder
     */
    public function scopeUnpaid($query): Builder
    {
        return $query->where('is_paid', false);
    }
}
