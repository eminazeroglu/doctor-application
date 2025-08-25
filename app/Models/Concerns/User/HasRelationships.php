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

    /*
    |--------------------------------------------------------------------------
    | NOTIFICATION RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    /**
     * İstifadəçinin notification cihazları
     */
    public function notificationDevices(): HasMany
    {
        return $this->hasMany(NotificationDevice::class, 'user_id');
    }

    /**
     * İstifadəçinin notification tənzimləmələri
     */
    public function notificationPreferences(): HasMany
    {
        return $this->hasMany(NotificationPreference::class, 'user_id');
    }

    /**
     * İstifadəçinin notification logları
     */
    public function notificationLogs(): HasMany
    {
        return $this->hasMany(NotificationLog::class, 'user_id');
    }

    /**
     * Son notification-lar
     */
    public function recentNotifications(): HasMany
    {
        return $this->notifications()->latest()->limit(10);
    }

    /*
    |--------------------------------------------------------------------------
    | NOTIFICATION HELPER METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * İstifadəçinin notification qəbul etmə tənzimləməsini yoxlamaq
     */
    public function canReceiveNotification(string $type, string $channel = 'in_app'): bool
    {
        $preference = $this->notificationPreferences()
            ->where('notification_type', $type)
            ->first();

        if (!$preference) {
            // Default tənzimləmələr
            return match($channel) {
                'email' => true,
                'sms' => false,
                'push' => true,
                'in_app' => true,
                default => true
            };
        }

        return match($channel) {
            'email' => $preference->email_enabled,
            'sms' => $preference->sms_enabled,
            'push' => $preference->push_enabled,
            'in_app' => $preference->in_app_enabled,
            default => true
        };
    }

    /**
     * İstifadəçinin aktiv cihazlarını əldə etmək
     */
    public function getActiveDevices(): Collection
    {
        return $this->notificationDevices()
            ->where('is_active', true)
            ->orderByDesc('last_used_at')
            ->get();
    }

    /**
     * İstifadəçinin notification tənzimləmələrini yaratmaq
     */
    public function createDefaultNotificationPreferences(): void
    {
        $defaultTypes = [
            'appointment',
            'review',
            'message',
            'system'
        ];

        foreach ($defaultTypes as $type) {
            $this->notificationPreferences()->updateOrCreate(
                [
                    'notification_type' => $type
                ],
                [
                    'email_enabled' => true,
                    'sms_enabled' => false,
                    'push_enabled' => true,
                    'in_app_enabled' => true,
                ]
            );
        }
    }
}
