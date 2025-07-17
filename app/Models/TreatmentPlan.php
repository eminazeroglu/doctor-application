<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TreatmentPlan extends BaseModel
{
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'appointment_id',
        'user_id',
        'diagnosis_id',
        'title',
        'description',
        'start_date',
        'end_date',
        'status',
        'goals',
        'notes',
        'custom_fields'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'custom_fields' => 'json'
    ];

    /**
     * Bu plana aid randevu
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Müalicə planının pasiyenti
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Müalicə planını tərtib edən həkim
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Əlaqəli diaqnoz
     */
    public function diagnosis(): BelongsTo
    {
        return $this->belongsTo(Diagnosis::class);
    }

    /**
     * Bu plandakı addımlar
     */
    public function steps(): HasMany
    {
        return $this->hasMany(TreatmentStep::class)->orderBy('order');
    }

    /**
     * Planın statusunu yeniləyir
     */
    public function updateStatus(string $status): bool
    {
        return $this->update(['status' => $status]);
    }
}
