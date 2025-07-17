<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class MedicalTest extends BaseModel
{
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'appointment_id',
        'user_id',
        'test_type',
        'name',
        'description',
        'status',
        'ordered_date',
        'due_date',
        'completed_date',
        'instructions',
        'custom_fields'
    ];

    protected $casts = [
        'ordered_date' => 'date',
        'due_date' => 'date',
        'completed_date' => 'date',
        'custom_fields' => 'json'
    ];

    /**
     * Bu testə aid randevu
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Testi keçən pasiyent
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Testi təyin edən həkim
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Bu testin nəticəsi
     */
    public function result(): HasOne
    {
        return $this->hasOne(TestResult::class);
    }

    /**
     * Test statusunu yeniləyir
     */
    public function updateStatus(string $status): bool
    {
        return $this->update(['status' => $status]);
    }
}
