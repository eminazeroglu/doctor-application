<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Services\Module\AppointmentService;
use Illuminate\Http\Request;

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
