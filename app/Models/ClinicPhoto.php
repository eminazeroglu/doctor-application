<?php

namespace App\Models;

use App\Traits\Model\HasImage;
use App\Traits\Model\HasUuid;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClinicPhoto extends Model
{
    use HasUuid, HasImage;

    protected $fillable = [
        'uuid',
        'clinic_id',
        'photo_path',
        'title',
        'description',
        'is_primary',
        'order'
    ];

    protected $casts = [
        'is_primary' => 'boolean'
    ];

    protected $appends = ['photo'];

    /**
     * Bu şəklin aid olduğu klinika
     */
    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function getImageFields(): array
    {
        return [
            'photo_path' => [
                'path' => 'clinic-gallery',
                'default_image' => 'default_clinic_logo.png'
            ]
        ];
    }
}
