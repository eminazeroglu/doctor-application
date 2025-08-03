<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\ApiController;
use App\Services\Module\ClinicService;

class ClinicController extends ApiController
{
    public function __construct(ClinicService $service)
    {
        parent::__construct($service, 'clinic');
    }

    // Add any additional methods here

    public function commonRules(): array
    {
        return [
            // Add validation rules for store method
        ];
    }
}
