<?php

namespace App\Models;

use App\Traits\Model\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClinicReview extends Model
{
    use HasUuid, SoftDeletes;

    protected $fillable = [
        'uuid',
        'clinic_id',
        'user_id',
        'appointment_id',
        'rating',
        'comment',
        'is_anonymous',
        'is_approved',
        'approved_by',
        'approved_at'
    ];

    protected $casts = [
        'rating' => 'integer',
        'is_anonymous' => 'boolean',
        'is_approved' => 'boolean',
        'approved_at' => 'datetime'
    ];

    /**
     * Bu rəyin aid olduğu klinika
     */
    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    /**
     * Rəyi yazan istifadəçi
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Bu rəyə əlaqəli randevu (varsa)
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Rəyi təsdiqləyən admin
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Rəy yazanın adını gizliliyi nəzərə alaraq qaytarır
     */
    public function getAuthorNameAttribute()
    {
        if ($this->is_anonymous) {
            return 'Anonim istifadəçi';
        }

        return $this->user?->fullname ?? 'Naməlum istifadəçi';
    }
}
