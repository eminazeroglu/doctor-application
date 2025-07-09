<?php

namespace App\Models\Concerns\User;

use App\Models\{Clinic,
    DoctorCertificate,
    DoctorEducation,
    DoctorExperience,
    DoctorLanguage,
    DoctorSpecialty,
    Payment,
    Role,
    Service,
    UserBlock,
    UserPreference,
    UserSocialLogin,
    UserLoginHistory,
    Comment,
    Complaint};

use Illuminate\Database\Eloquent\Relations\{BelongsTo, BelongsToMany, HasMany, HasOne, MorphMany};

trait HasRelationships
{
    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS - Əlaqələr
    |--------------------------------------------------------------------------
    */
    // Əsas əlaqələr
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function preferences(): HasOne
    {
        return $this->hasOne(UserPreference::class);
    }

    public function socialLogins(): HasMany
    {
        return $this->hasMany(UserSocialLogin::class);
    }

    public function loginHistory(): HasMany
    {
        return $this->hasMany(UserLoginHistory::class);
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function complaints(): MorphMany
    {
        return $this->morphMany(Complaint::class, 'complaintable');
    }


    // Payments
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * İstifadəçinin blok etdiyi istifadəçilər
     */
    public function blockedUsers()
    {
        return $this->hasMany(UserBlock::class, 'blocker_id');
    }

    /**
     * İstifadəçini blok edən istifadəçilər
     */
    public function blockedByUsers()
    {
        return $this->hasMany(UserBlock::class, 'blocked_id');
    }

    /**
     * Service
     * */
    public function medicalServices(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'doctor_service', 'doctor_id', 'service_id')
            ->withPivot(['custom_price', 'custom_duration', 'custom_fields'])
            ->withTimestamps();
    }

    /**
     * Həkimin ixtisasları
     */
    public function doctorSpecialties()
    {
        return $this->hasMany(DoctorSpecialty::class);
    }

    /**
     * Həkimin əsas ixtisası
     */
    public function primarySpecialty()
    {
        return $this->hasOne(DoctorSpecialty::class)->where('is_primary', true);
    }

    /**
     * Həkimin təhsil məlumatları
     */
    public function education()
    {
        return $this->hasMany(DoctorEducation::class)->orderBy('order');
    }

    /**
     * Həkimin iş təcrübəsi
     */
    public function experience()
    {
        return $this->hasMany(DoctorExperience::class)->orderBy('order');
    }

    /**
     * Həkimin sertifikatları
     */
    public function certificates()
    {
        return $this->hasMany(DoctorCertificate::class)->orderBy('order');
    }

    /**
     * Həkimin dil bilikləri
     */
    public function languages()
    {
        return $this->hasMany(DoctorLanguage::class);
    }

    /**
     * Həkimin işlədiyi klinikalar
     */
    public function clinics()
    {
        return $this->belongsToMany(Clinic::class, 'doctor_clinics')
            ->withPivot(['is_primary', 'working_hours', 'custom_fields'])
            ->withTimestamps();
    }

    /**
     * Həkimin əsas klinikası
     */
    public function primaryClinic()
    {
        return $this->belongsToMany(Clinic::class, 'doctor_clinics')
            ->wherePivot('is_primary', true)
            ->withPivot(['working_hours', 'custom_fields'])
            ->withTimestamps()
            ->first();
    }
}
