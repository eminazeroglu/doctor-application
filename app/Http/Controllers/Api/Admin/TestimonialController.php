<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\ApiController;
use App\Services\Module\TestimonialService;

class TestimonialController extends ApiController
{
    public function __construct(TestimonialService $service)
    {
        parent::__construct($service, 'testimonial');
    }

    // Add any additional methods here

    public function commonRules(): array
    {
        return [
            // Add validation rules for store method
        ];
    }
}
