<?php

namespace App\Http\Requests\Translation;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class LanguageRequest extends BaseRequest
{
    public function __construct()
    {
        parent::__construct('language');
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'locale' => [
                'required',
                'string',
                'size:2',
                Rule::unique('languages')->ignore($this->route('language')),
            ],
            'is_active' => 'boolean',
        ];
    }
}
