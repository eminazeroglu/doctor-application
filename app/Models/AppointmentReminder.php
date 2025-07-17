<?php

namespace App\Models;

use App\Enums\AppointmentReminderTypeEnum;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppointmentReminder extends Model
{
    protected $fillable = [
        'appointment_id',
        'type',
        'scheduled_at',
        'sent_at',
        'is_sent',
        'message',
        'meta_data'
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'is_sent' => 'boolean',
        'meta_data' => 'json'
    ];

    /**
     * Randevu əlaqəsi
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Xatırlatmanı göndərilmiş kimi işarələyir
     */
    public function markAsSent(): bool
    {
        return $this->update([
            'is_sent' => true,
            'sent_at' => now()
        ]);
    }

    /**
     * Növ təsvirini qaytarır
     */
    public function typeDescription(): Attribute
    {
        return new Attribute(
            get: function () {
                return AppointmentReminderTypeEnum::getDescription($this->type);
            }
        );
    }
}
