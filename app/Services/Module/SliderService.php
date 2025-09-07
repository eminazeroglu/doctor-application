<?php

namespace App\Services\Module;

use App\Repositories\Module\SliderRepository;
use App\Services\BaseCrudService;

class SliderService extends BaseCrudService
{
    public function __construct(SliderRepository $repository)
    {
        parent::__construct($repository);
    }

    // Add any additional methods here
}
