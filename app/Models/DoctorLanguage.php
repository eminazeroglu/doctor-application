<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute as AttributeAlias;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorLanguage extends BaseModel
{

    /**
     * Kütləvi təyin edilə bilən atributlar.
     * @var array
     */
    protected $fillable = [
        'doctor_id',
        'language',
        'proficiency'
    ];

    /**
     * Avtomatik əlavə edilən atributlar.
     * @var array
     */
    protected $appends = ['proficiency_text'];

    /**
     * Dil bacarığı səviyyəsinin mətn təsvirini qaytarır.
     * @return AttributeAlias
     */
    public function proficiencyText(): AttributeAlias
    {
       return new AttributeAlias(
           get: function () {
               return match($this->proficiency) {
                   'native' => 'Ana dili',
                   'fluent' => 'Sərbəst',
                   'intermediate' => 'Orta',
                   'basic' => 'Baza',
                   default => $this->proficiency
               };
           }
       );
    }

    /**
     * Dil biliyi qeydinə aid həkim əlaqəsi.
     * @return BelongsTo
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }
}
