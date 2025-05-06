<?php

namespace App\Http\Requests\User;

use App\Http\Requests\BaseRequest;

class UpdateEmailRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'email' => 'required|string|email|max:255|unique:users,email,' . $this->user()->id,
        ];
    }
}
