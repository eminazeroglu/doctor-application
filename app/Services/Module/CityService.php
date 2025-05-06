<?php

namespace App\Services\Module;

use App\Repositories\Module\CityRepository;
use App\Services\BaseCrudService;

class CityService extends BaseCrudService
{
    public function __construct(CityRepository $repository)
    {
        parent::__construct($repository);
    }

    // Add any additional methods here
}
