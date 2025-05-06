<?php

namespace App\Http\Requests\User;

use App\Http\Requests\BaseRequest;

class UpdatePreferencesRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'dark_mode' => 'boolean',
            'language' => 'string|max:10',
            'notification_settings' => 'array',
            'content_preferences' => 'array',
        ];
    }
}
