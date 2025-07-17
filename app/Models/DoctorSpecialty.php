<?php

namespace App\Models;

use App\Traits\Model\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorSpecialty extends Model
{
    use HasUuid;

    protected $fillable = [
        'uuid',
        'user_id',
        'category_id',
        'is_primary',
        'description',
        'experience_years',
        'custom_fields'
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'experience_years' => 'integer',
        'custom_fields' => 'json'
    ];

    /**
     * Bu ixtisasın sahibi olan həkim
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Həkimin ixtisası (kateqoriya)
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
}
