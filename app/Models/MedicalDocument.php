<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MedicalDocument extends BaseModel
{
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'appointment_id',
        'user_id',
        'document_type',
        'title',
        'content',
        'file_path',
        'issue_date',
        'expiry_date',
        'is_signed',
        'custom_fields'
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'is_signed' => 'boolean',
        'custom_fields' => 'json'
    ];

    /**
     * Bu sənədə aid randevu
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Sənədin aid olduğu pasiyent
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Sənədi yaradan həkim
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
