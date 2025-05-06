<?php

namespace App\Http\Requests\Notification;

use App\Enums\NotificationPriorityEnum;
use App\Http\Requests\BaseRequest;

class CreateNotificationRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'action_url' => 'nullable|url',
            'priority' => 'nullable|string|in:'. implode(',', NotificationPriorityEnum::getValues()),

            // User seçim qaydaları - yalnız biri olmalıdır
            'user_ids' => 'required|array|exists:users,id',

            /*'user_types' => [
                'nullable',
                'array',
                'in:' . implode(',', UserTypeEnum::getValues())
            ],*/

            // Digər qaydalar
            'send_at' => 'nullable|date|after:now',
        ];
    }
}
