<?php

namespace App\Services\Module;

use App\Repositories\Module\RegionRepository;
use App\Services\BaseCrudService;

class RegionService extends BaseCrudService
{
    public function __construct(RegionRepository $repository)
    {
        parent::__construct($repository);
    }

    // Add any additional methods here
}
