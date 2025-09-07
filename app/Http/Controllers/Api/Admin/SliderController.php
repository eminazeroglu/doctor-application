<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\ApiController;
use App\Rules\Base64ImageControlRule;
use App\Rules\ImageBase64Rule;
use App\Services\Module\SliderService;

class SliderController extends ApiController
{
    public function __construct(SliderService $service)
    {
        parent::__construct($service, 'slider');
    }

    // Add any additional methods here

    public function commonRules(): array
    {
        return [
            'photo_path' => ['required', 'string', new ImageBase64Rule, new Base64ImageControlRule],
            'translates' => ['required', 'array'],
            'translates.*.title' => ['required', 'string', 'max:255'],
            'translates.*.description' => ['required', 'string'],
        ];
    }
}
