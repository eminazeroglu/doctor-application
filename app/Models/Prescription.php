<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Prescription extends BaseModel
{
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'prescription_number',
        'appointment_id',
        'user_id',
        'diagnosis_id',
        'issue_date',
        'expiry_date',
        'is_digital',
        'status',
        'notes',
        'custom_fields'
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'is_digital' => 'boolean',
        'custom_fields' => 'json'
    ];

    /**
     * Bu reseptə aid randevu
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Resepti alan pasiyent
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Resepti yazan həkim
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
     * Bu reseptdəki dərmanlar
     */
    public function items(): HasMany
    {
        return $this->hasMany(PrescriptionItem::class);
    }

    /**
     * Reseptin statusunu yeniləyir
     */
    public function updateStatus(string $status): bool
    {
        return $this->update(['status' => $status]);
    }
}
