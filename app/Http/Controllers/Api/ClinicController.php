<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Services\Module\ClinicService;
use Illuminate\Http\Request;

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
