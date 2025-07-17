<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrescriptionItem extends Model
{
    protected $fillable = [
        'prescription_id',
        'medication_name',
        'dosage',
        'frequency',
        'duration',
        'quantity',
        'instructions',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'quantity' => 'integer'
    ];

    /**
     * Bu dərmanın aid olduğu resept
     */
    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class);
    }
}
