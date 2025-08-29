<?php

namespace App\Models\Concerns\User;

use App\Models\{Appointment,
    Doctor,
    Notification,
    NotificationDevice,
    NotificationLog,
    NotificationPreference,
    Patient,
    Payment,
    Review,
    Role,
    Service,
    UserBlock,
    UserPreference,
    UserSocialLogin,
    UserLoginHistory,
    Comment,
    Complaint};

use Illuminate\Database\Eloquent\Relations\{BelongsTo, BelongsToMany, HasMany, HasOne, MorphMany};
use Illuminate\Database\Eloquent\Collection;

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
    public function blockedUsers(): HasMany
    {
        return $this->hasMany(UserBlock::class, 'blocker_id');
    }

    /**
     * İstifadəçini blok edən istifadəçilər
     */
    public function blockedByUsers(): HasMany
    {
        return $this->hasMany(UserBlock::class, 'blocked_id');
    }

    // Doctor & Patient əlaqələri
    public function doctor(): HasOne
    {
        return $this->hasOne(Doctor::class);
    }

    public function patient(): HasOne
    {
        return $this->hasOne(Patient::class);
    }

    // Randevular
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'patient_id');
    }

    public function doctorAppointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'doctor_id');
    }

    // Rəylər
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'patient_id');
    }

    public function doctorReviews(): HasMany
    {
        return $this->hasMany(Review::class, 'doctor_id');
    }
}
