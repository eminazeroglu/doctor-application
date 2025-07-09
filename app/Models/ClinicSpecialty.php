<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class ClinicSpecialty extends Pivot
{
    protected $table = 'clinic_specialty';

    protected $fillable = [
        'clinic_id',
        'category_id',
        'is_primary',
        'description',
        'custom_fields'
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'custom_fields' => 'json'
    ];

    /**
     * Bu əlaqənin aid olduğu klinika
     */
    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    /**
     * Bu əlaqənin aid olduğu ixtisas
     */
    public function specialty(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
}
