<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute as AttributeAlias;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientFamilyMember extends BaseModel
{
    /**
     * Kütləvi təyin edilə bilən atributlar.
     * @var array
     */
    protected $fillable = [
        'uuid',
        'patient_id',
        'member_patient_id',
        'name',
        'surname',
        'birthdate',
        'gender',
        'relation',
        'phone',
        'email',
        'notes',
        'is_emergency_contact',
        'is_dependent'
    ];

    /**
     * Verilənlər tipini çevrilməli olan atributlar.
     * @var array
     */
    protected $casts = [
        'birthdate' => 'date',
        'is_emergency_contact' => 'boolean',
        'is_dependent' => 'boolean',
    ];

    /**
     * Avtomatik əlavə edilən atributlar.
     * @var array
     */
    protected $appends = ['full_name', 'age', 'relation_text', 'is_registered_patient'];

    /**
     * Ailə üzvünün tam adını qaytarır.
     * @return AttributeAlias
     */
    public function fullName(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                if ($this->member_patient_id) {
                    return $this->memberPatient->full_name;
                }

                if ($this->name || $this->surname) {
                    return ($this->name ?? '') . ' ' . ($this->surname ?? '');
                }

                return null;
            }
        );
    }

    /**
     * Ailə üzvünün yaşını hesablayır.
     * @return AttributeAlias
     */
    public function age(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                if ($this->member_patient_id) {
                    return $this->memberPatient->age;
                }

                if (!$this->birthdate) {
                    return null;
                }

                return Carbon::parse($this->birthdate)->age;
            }
        );
    }

    /**
     * Qohumluq əlaqəsinin mətn təsvirini qaytarır.
     * @return AttributeAlias
     */
    public function relationText(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                return match($this->relation) {
                    'parent' => 'Valideyn',
                    'child' => 'Uşaq',
                    'spouse' => 'Həyat yoldaşı',
                    'sibling' => 'Qardaş/Bacı',
                    'grandparent' => 'Baba/Nənə',
                    'grandchild' => 'Nəvə',
                    'other' => 'Digər',
                    default => $this->relation
                };
            }
        );
    }

    /**
     * Ailə üzvünün sistemdə qeydiyyatdan keçmiş xəstə olub-olmadığını yoxlayır.
     * @return AttributeAlias
     */
    public function isRegisteredPatient(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                return $this->member_patient_id !== null;
            }
        );
    }

    /**
     * Ailə üzvünə aid xəstə əlaqəsi.
     * @return BelongsTo
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Ailə üzvü kimi qeydiyyatdan keçmiş xəstə əlaqəsi.
     * @return BelongsTo
     */
    public function memberPatient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'member_patient_id');
    }

    /**
     * Qohumluq əlaqəsinə görə axtarış.
     * @param Builder $query
     * @param string $relation
     * @return Builder
     */
    public function scopeWithRelation(Builder $query, string $relation): Builder
    {
        return $query->where('relation', $relation);
    }

    /**
     * Təcili əlaqə şəxsləri axtarış.
     * @param Builder $query
     * @return Builder
     */
    public function scopeEmergencyContacts(Builder $query): Builder
    {
        return $query->where('is_emergency_contact', true);
    }

    /**
     * Asılı şəxsləri axtarış.
     * @param Builder $query
     * @return Builder
     */
    public function scopeDependents(Builder $query): Builder
    {
        return $query->where('is_dependent', true);
    }

    /**
     * Sistemdə qeydiyyatdan keçmiş xəstələri axtarış.
     * @param Builder $query
     * @return Builder
     */
    public function scopeRegisteredPatients(Builder $query): Builder
    {
        return $query->whereNotNull('member_patient_id');
    }
}
