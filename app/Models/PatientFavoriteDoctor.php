<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class PatientFavoriteDoctor extends Pivot
{
    protected $table = 'patient_favorite_doctors';
    /**
     * Kütləvi təyin edilə bilən atributlar.
     * @var array
     */
    protected $fillable = [
        'patient_id',
        'doctor_id',
        'note'
    ];

    /**
     * Verilənlər tipini çevrilməli olan atributlar.
     * @var array
     */
    protected $casts = [];
}
