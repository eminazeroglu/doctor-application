<?php

namespace App\Http\Requests\Translation;

use App\Http\Requests\BaseRequest;

class TranslateRequest extends BaseRequest
{
    public function __construct()
    {
        parent::__construct('language');
    }

    public function rules(): array
    {
        return [
            'key' => 'required|string|max:255,unique:translates,key',
            'translation' => 'required|array',
            'translation.*' => 'required',
        ];
    }
}
