<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class PatientFavoriteClinic extends Pivot
{
    /**
     * Kütləvi təyin edilə bilən atributlar.
     * @var array
     */
    protected $fillable = [
        'patient_id',
        'clinic_id',
        'note'
    ];

    /**
     * Verilənlər tipini çevrilməli olan atributlar.
     * @var array
     */
    protected $casts = [];
}
