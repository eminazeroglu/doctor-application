<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\ApiController;
use App\Services\Module\FaqService;

class FaqController extends ApiController
{
    public function __construct(FaqService $service)
    {
        parent::__construct($service, 'faq');
    }

    // Add any additional methods here

    public function commonRules(): array
    {
        return [
            'translates' => 'required|array',
            'translates.*' => 'required',
            'translates.*.question' => 'required',
            'translates.*.answer' => 'required',
        ];
    }
}
