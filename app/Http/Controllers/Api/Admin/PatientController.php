<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\ApiController;
use App\Services\Module\PatientService;

class PatientController extends ApiController
{
    public function __construct(PatientService $service)
    {
        parent::__construct($service, 'patient');
    }

    // Add any additional methods here

    public function commonRules(): array
    {
        return [
            // Add validation rules for store method
        ];
    }
}
