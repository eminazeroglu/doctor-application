<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorAttributeValue extends BaseModel
{
    protected $fillable = [
        'doctor_id',            // Aid olduğu həkim
        'attribute_id',         // Atributun ID-si
        'attribute_option_id',  // Seçim dəyəri (select tipli atributlar üçün)
        'value'                 // Atributun dəyəri (text və ya JSON formatında)
    ];

    /*
    |--------------------------------------------------------------------------
    | ƏLAQƏLƏR
    |--------------------------------------------------------------------------
    */

    /**
     * Aid olduğu elan
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    /**
     * Atributun özü
     */
    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }

    /**
     * Seçilmiş option (select tipli atributlar üçün)
     */
    public function attributeOption(): BelongsTo
    {
        return $this->belongsTo(AttributeOption::class);
    }
}
