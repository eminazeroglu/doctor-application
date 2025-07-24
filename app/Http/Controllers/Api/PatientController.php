<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Services\Module\PatientService;
use Illuminate\Http\Request;

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
