<?php

namespace App\Models;

use App\Traits\Model\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPreference extends Model
{
    use HasUuid;

    protected $fillable = [
        'user_id',
        'dark_mode',
        'language',
        'notification_settings',
        'content_preferences',
        'timezone',
        'email_frequency',
        'show_email',
        'show_profile_views',
        'privacy_settings',
    ];

    protected $casts = [
        'dark_mode' => 'boolean',
        'notification_settings' => 'json',
        'content_preferences' => 'json',
        'show_email' => 'boolean',
        'show_profile_views' => 'boolean',
        'privacy_settings' => 'json',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */
    public function toggleDarkMode(): void
    {
        $this->update(['dark_mode' => !$this->dark_mode]);
    }

    public function updateNotificationSetting(string $key, bool $value): void
    {
        $settings = $this->notification_settings;
        $settings[$key] = $value;
        $this->update(['notification_settings' => $settings]);
    }

    public function updateContentPreference(string $key, $value): void
    {
        $preferences = $this->content_preferences;
        $preferences[$key] = $value;
        $this->update(['content_preferences' => $preferences]);
    }

    public function updatePrivacySetting(string $key, $value): void
    {
        $settings = $this->privacy_settings;
        $settings[$key] = $value;
        $this->update(['privacy_settings' => $settings]);
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */
    public function scopeWithDarkMode($query)
    {
        return $query->where('dark_mode', true);
    }

    public function scopeWithLanguage($query, $language)
    {
        return $query->where('language', $language);
    }
}
