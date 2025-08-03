<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\ApiController;
use App\Services\Module\PaymentService;

class PaymentController extends ApiController
{
    public function __construct(PaymentService $service)
    {
        parent::__construct($service, 'payment');
    }

    // Add any additional methods here

    public function commonRules(): array
    {
        return [
            // Add validation rules for store method
        ];
    }
}
