<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\ApiController;
use App\Services\Module\CountryService;

class CountryController extends ApiController
{
    public function __construct(CountryService $service)
    {
        parent::__construct($service, 'country');
    }

    // Add any additional methods here

    public function commonRules(): array
    {
        return [
            'translates' => 'required|array',
            'translates.*.name' => 'required',
            'map_location' => 'required|array',
            'map_location.lat' => 'required',
            'map_location.lng' => 'required',
        ];
    }
}
