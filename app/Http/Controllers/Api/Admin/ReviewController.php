<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\ApiController;
use App\Services\Module\ReviewService;

class ReviewController extends ApiController
{
    public function __construct(ReviewService $service)
    {
        parent::__construct($service, 'review');
    }

    // Add any additional methods here

    public function commonRules(): array
    {
        return [
            // Add validation rules for store method
        ];
    }
}
