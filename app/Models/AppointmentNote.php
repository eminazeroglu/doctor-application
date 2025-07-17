<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppointmentNote extends BaseModel
{
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'appointment_id',
        'chief_complaint',
        'symptoms',
        'examination_notes',
        'vital_signs',
        'history',
        'additional_notes',
        'status',
        'custom_fields'
    ];

    protected $casts = [
        'vital_signs' => 'json',
        'custom_fields' => 'json'
    ];

    /**
     * Bu qeydə aid randevu
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Qeydi yazan həkim
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Qeyd statusunu yeniləyir
     */
    public function updateStatus(string $status): bool
    {
        return $this->update(['status' => $status]);
    }
}
