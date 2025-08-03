<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\ApiController;
use App\Services\Module\AppointmentService;

class AppointmentController extends ApiController
{
    public function __construct(AppointmentService $service)
    {
        parent::__construct($service, 'appointment');
    }

    // Add any additional methods here

    public function commonRules(): array
    {
        return [
            // Add validation rules for store method
        ];
    }
}
