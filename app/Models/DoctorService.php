<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorService extends Model
{
    public $incrementing = true;

    protected $table = 'doctor_medical_service';

    protected $fillable = [
        'doctor_id',
        'medical_service_id',
        'custom_price',
        'custom_duration',
        'custom_fields'
    ];

    protected $casts = [
        'custom_price' => 'decimal:2',
        'custom_duration' => 'integer',
        'custom_fields' => 'object'
    ];

    // Əlaqələr
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id');
    }
}
