<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute as AttributeAlias;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends BaseModel
{
    use SoftDeletes;

    /**
     * Kütləvi təyin edilə bilən atributlar.
     * @var array
     */
    protected $fillable = [
        'uuid',
        'user_id',
        'medical_history',
        'allergies',
        'chronic_diseases',
        'current_medications',
        'family_medical_history',
        'additional_info',
        'blood_type',
        'height',
        'weight',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relation',
        'insurance_provider',
        'insurance_policy_number',
        'insurance_expiry_date'
    ];

    /**
     * Verilənlər tipini çevrilməli olan atributlar.
     * @var array
     */
    protected $casts = [
        'additional_info' => 'json',
        'height' => 'float',
        'weight' => 'float',
        'insurance_expiry_date' => 'date',
    ];

    /**
     * Avtomatik əlavə edilən atributlar.
     * @var array
     */
    protected $appends = ['bmi', 'bmi_category', 'is_insurance_expired', 'full_name', 'age'];

    /**
     * Xəstənin bədən kütlə indeksini hesablayır.
     * @return AttributeAlias
     */
    public function bmi(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                if (!$this->height || !$this->weight) {
                    return null;
                }

                // BMI = çəki(kg) / (boy(m) * boy(m))
                $heightInMeters = $this->height / 100; // santimetrdən metrə çevirmə
                return round($this->weight / ($heightInMeters * $heightInMeters), 2);
            }
        );
    }

    /**
     * Xəstənin bədən kütlə indeksi kateqoriyasını qaytarır.
     * @return AttributeAlias
     */
    public function bmiCategory(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                $bmi = $this->bmi;

                if ($bmi === null) {
                    return null;
                }

                if ($bmi < 18.5) {
                    return 'Çəki çatışmazlığı';
                } elseif ($bmi < 25) {
                    return 'Normal çəki';
                } elseif ($bmi < 30) {
                    return 'Artıq çəki';
                } else {
                    return 'Piylənmə';
                }
            }
        );
    }

    /**
     * Xəstənin sığorta polisinin müddətinin bitib-bitmədiyini yoxlayır.
     * @return AttributeAlias
     */
    public function isInsuranceExpired(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                if (!$this->insurance_expiry_date) {
                    return null;
                }

                return $this->insurance_expiry_date->isPast();
            }
        );
    }

    /**
     * Xəstənin tam adını qaytarır.
     * @return AttributeAlias
     */
    public function fullName(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                if ($this->user) {
                    return $this->user->name . ' ' . $this->user->surname;
                }
                return null;
            }
        );
    }

    /**
     * Xəstənin yaşını hesablayır.
     * @return AttributeAlias
     */
    public function age(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                if (!$this?->user?->birthdate) {
                    return null;
                }

                return Carbon::parse(now())->diff($this?->user?->birthdate)->format('%y');
            }
        );
    }

    /**
     * Xəstəyə aid istifadəçi əlaqəsi.
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Xəstənin tibbi qeydləri əlaqəsi.
     * @return HasMany
     */
    public function medicalRecords(): HasMany
    {
        return $this->hasMany(PatientMedicalRecord::class);
    }

    /**
     * Xəstənin tibbi sənədləri əlaqəsi.
     * @return HasMany
     */
    public function documents(): HasMany
    {
        return $this->hasMany(PatientDocument::class);
    }

    /**
     * Xəstənin randevuları əlaqəsi.
     * @return HasMany
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Xəstənin dərman qeydləri əlaqəsi.
     * @return HasMany
     */
    public function medications(): HasMany
    {
        return $this->hasMany(PatientMedication::class);
    }

    /**
     * Xəstənin allergiya qeydləri əlaqəsi.
     * @return HasMany
     */
    public function patientAllergies(): HasMany
    {
        return $this->hasMany(PatientAllergy::class);
    }

    /**
     * Xəstənin ailə üzvləri əlaqəsi.
     * @return HasMany
     */
    public function familyMembers(): HasMany
    {
        return $this->hasMany(PatientFamilyMember::class);
    }

    /**
     * Xəstənin favori həkimləri əlaqəsi.
     * @return BelongsToMany
     */
    public function favoriteDoctors(): BelongsToMany
    {
        return $this->belongsToMany(Doctor::class, 'patient_favorite_doctors')
            ->withPivot(['note'])
            ->withTimestamps();
    }

    /**
     * Xəstənin favori klinikları əlaqəsi.
     * @return BelongsToMany
     */
    public function favoriteClinics(): BelongsToMany
    {
        return $this->belongsToMany(Clinic::class, 'patient_favorite_clinics')
            ->withPivot(['note'])
            ->withTimestamps();
    }

    /**
     * Xəstənin rəyləri əlaqəsi.
     * @return HasMany
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Xəstənin aktiv randevularını qaytarır.
     * @return HasMany
     */
    public function upcomingAppointments(): HasMany
    {
        return $this->appointments()
            ->where('start_time', '>', now())
            ->whereIn('appointment_status', ['pending', 'confirmed', 'rescheduled'])
            ->orderBy('start_time', 'asc');
    }

    /**
     * Xəstənin keçmiş randevularını qaytarır.
     * @return HasMany
     */
    public function pastAppointments(): HasMany
    {
        return $this->appointments()
            ->where('start_time', '<', now())
            ->orderBy('start_time', 'desc');
    }

    /**
     * Xəstənin son tibbi qeydini qaytarır.
     * @return PatientMedicalRecord|null
     */
    public function getLastMedicalRecord(): ?PatientMedicalRecord
    {
        return $this->medicalRecords()
            ->orderBy('record_date', 'desc')
            ->first();
    }

    /**
     * Xəstənin aktiv dərmanlarını qaytarır.
     * @return HasMany
     */
    public function activeMedications(): HasMany
    {
        return $this->medications()
            ->where('is_active', true)
            ->where(function($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', now()->toDateString());
            });
    }

    /**
     * Xəstənin təcili əlaqə şəxsi olan ailə üzvlərini qaytarır.
     * @return HasMany
     */
    public function emergencyContacts(): HasMany
    {
        return $this->familyMembers()
            ->where('is_emergency_contact', true);
    }

    /**
     * Xəstənin asılı şəxslərini qaytarır.
     * @return HasMany
     */
    public function dependents(): HasMany
    {
        return $this->familyMembers()
            ->where('is_dependent', true);
    }

    /**
     * Ada görə xəstə axtarışı.
     * @param Builder $query
     * @param string $name
     * @return Builder
     */
    public function scopeSearchByName(Builder $query, string $name): Builder
    {
        return $query->whereHas('user', function($q) use ($name) {
            $q->where('name', 'like', "%{$name}%")
                ->orWhere('surname', 'like', "%{$name}%");
        });
    }

    /**
     * Telefon nömrəsinə görə xəstə axtarışı.
     * @param Builder $query
     * @param string $phone
     * @return Builder
     */
    public function scopeSearchByPhone(Builder $query, string $phone): Builder
    {
        return $query->whereHas('user', function($q) use ($phone) {
            $q->where('phone', 'like', "%{$phone}%");
        });
    }

    /**
     * E-poçta görə xəstə axtarışı.
     * @param Builder $query
     * @param string $email
     * @return Builder
     */
    public function scopeSearchByEmail(Builder $query, string $email): Builder
    {
        return $query->whereHas('user', function($q) use ($email) {
            $q->where('email', 'like', "%{$email}%");
        });
    }

    /**
     * Xəstənin tam profilini yükləyir.
     * @return Patient
     */
    public function loadFullProfile(): Patient
    {
        return $this->load([
            'user',
            'medicalRecords' => function($query) {
                $query->orderBy('record_date', 'desc');
            },
            'documents' => function($query) {
                $query->orderBy('document_date', 'desc');
            },
            'medications' => function($query) {
                $query->where('is_active', true);
            },
            'patientAllergies',
            'familyMembers',
            'upcomingAppointments' => function($query) {
                $query->with('doctor', 'clinic', 'service');
            }
        ]);
    }
}
