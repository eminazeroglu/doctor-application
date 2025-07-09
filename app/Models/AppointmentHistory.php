<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppointmentHistory extends Model
{
    protected $fillable = [
        'appointment_id',
        'status_id',
        'user_id',
        'action',
        'notes',
        'changes',
        'meta_data'
    ];

    protected $casts = [
        'changes' => 'json',
        'meta_data' => 'json'
    ];

    protected $appends = ['action_description', 'user_name'];

    /**
     * Randevu əlaqəsi
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * İstifadəçi əlaqəsi
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * İstifadəçi adını qaytarır
     */
    public function userName(): Attribute
    {
        return new Attribute(
            get: function() {
                return $this->user ? $this->user->fullname : 'Sistem';
            }
        );
    }

    /**
     * Əməliyyatın təsvirini qaytarır
     */
    public function actionDescription(): Attribute
    {
        return new Attribute(
            get: function () {
                return match ($this->action) {
                    'create' => 'Randevu yaradıldı',
                    'update' => 'Randevu yeniləndi',
                    'confirm' => 'Randevu təsdiqləndi',
                    'cancel' => 'Randevu ləğv edildi',
                    'complete' => 'Randevu tamamlandı',
                    'no_show' => 'Pasient gəlmədi',
                    'reschedule' => 'Randevu vaxtı dəyişdirildi',
                    'payment' => 'Ödəniş edildi',
                    'note_added' => 'Qeyd əlavə edildi',
                    default => $this->action,
                };
            }
        );
    }
}
