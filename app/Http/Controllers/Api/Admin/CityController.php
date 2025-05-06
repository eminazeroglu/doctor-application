<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\ApiController;
use App\Services\Module\CityService;

class CityController extends ApiController
{
    public function __construct(CityService $service)
    {
        parent::__construct($service, 'city');
    }

    public function commonRules(): array
    {
        return [
            'country_id' => 'required|exists:countries,id',
            'translates' => 'required|array',
            'translates.*.name' => 'required',
            'map_location' => 'required|array',
            'map_location.lat' => 'required',
            'map_location.lng' => 'required',
        ];
    }
}
