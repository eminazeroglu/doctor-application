<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Services\Module\ReviewService;
use Illuminate\Http\Request;

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
