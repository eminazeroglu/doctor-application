<?php

namespace App\Services\Module;

use App\Repositories\Module\SubwayRepository;
use App\Services\BaseCrudService;

class SubwayService extends BaseCrudService
{
    public function __construct(SubwayRepository $repository)
    {
        parent::__construct($repository);
    }

    // Add any additional methods here
}
