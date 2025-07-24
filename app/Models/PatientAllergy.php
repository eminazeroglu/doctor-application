<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute as AttributeAlias;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientAllergy extends BaseModel
{
    /**
     * Kütləvi təyin edilə bilən atributlar.
     * @var array
     */
    protected $fillable = [
        'uuid',
        'patient_id',
        'allergen_name',
        'allergen_type',
        'severity',
        'reactions',
        'diagnosis_date',
        'notes'
    ];

    /**
     * Verilənlər tipini çevrilməli olan atributlar.
     * @var array
     */
    protected $casts = [
        'diagnosis_date' => 'date',
    ];

    /**
     * Avtomatik əlavə edilən atributlar.
     * @var array
     */
    protected $appends = ['allergen_type_text', 'severity_text'];

    /**
     * Allergen növünün mətn təsvirini qaytarır.
     * @return AttributeAlias
     */
    public function allergenTypeText(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                return match($this->allergen_type) {
                    'food' => 'Qida',
                    'drug' => 'Dərman',
                    'environmental' => 'Ətraf mühit',
                    'animal' => 'Heyvan',
                    'insect' => 'Həşərat',
                    'other' => 'Digər',
                    default => $this->allergen_type
                };
            }
        );
    }

    /**
     * Allergiya şiddətinin mətn təsvirini qaytarır.
     * @return AttributeAlias
     */
    public function severityText(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                if (!$this->severity) {
                    return null;
                }

                return match($this->severity) {
                    'mild' => 'Yüngül',
                    'moderate' => 'Orta',
                    'severe' => 'Ağır',
                    default => $this->severity
                };
            }
        );
    }

    /**
     * Allergiya qeydinə aid xəstə əlaqəsi.
     * @return BelongsTo
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Allergen növünə görə axtarış.
     * @param Builder $query
     * @param string $type
     * @return Builder
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('allergen_type', $type);
    }

    /**
     * Allergiya şiddətinə görə axtarış.
     * @param Builder $query
     * @param string $severity
     * @return Builder
     */
    public function scopeWithSeverity(Builder $query, string $severity): Builder
    {
        return $query->where('severity', $severity);
    }

    /**
     * Allergen adına görə axtarış.
     * @param Builder $query
     * @param string $allergenName
     * @return Builder
     */
    public function scopeWithAllergenName(Builder $query, string $allergenName): Builder
    {
        return $query->where('allergen_name', 'like', "%{$allergenName}%");
    }

    /**
     * Ağır allergiyaları axtarış.
     * @param Builder $query
     * @return Builder
     */
    public function scopeSevere(Builder $query): Builder
    {
        return $query->where('severity', 'severe');
    }
}
