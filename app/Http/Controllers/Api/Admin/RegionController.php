<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\ApiController;
use App\Services\Module\RegionService;

class RegionController extends ApiController
{
    public function __construct(RegionService $service)
    {
        parent::__construct($service, 'region');
    }

    public function commonRules(): array
    {
        return [
            'country_id' => 'required|exists:countries,id',
            'city_id' => 'required|exists:cities,id',
            'translates' => 'required|array',
            'translates.*.name' => 'required',
            'map_location' => 'required|array',
            'map_location.lat' => 'required',
            'map_location.lng' => 'required',
        ];
    }
}
