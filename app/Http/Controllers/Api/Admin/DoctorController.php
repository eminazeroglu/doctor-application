<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\ApiController;
use App\Services\Module\DoctorService;

class DoctorController extends ApiController
{
    public function __construct(DoctorService $service)
    {
        parent::__construct($service, 'doctor');
    }

    // Add any additional methods here

    public function commonRules(): array
    {
        return [
            // Add validation rules for store method
        ];
    }
}
