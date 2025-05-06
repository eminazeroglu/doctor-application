<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class UserPreferenceResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'dark_mode' => $this->dark_mode,
            'language' => $this->language,
            'timezone' => $this->timezone,
            'email_frequency' => $this->email_frequency,
            'show_email' => $this->show_email,
            'show_profile_views' => $this->show_profile_views,
            'privacy_settings' => $this->privacy_settings,
            'notification_settings' => $this->notification_settings,
            'content_preferences' => $this->content_preferences,
        ];
    }
}
