<?php

namespace App\Services\Module;

use App\Repositories\Module\ClinicRepository;
use App\Services\BaseCrudService;

class ClinicService extends BaseCrudService
{
    public function __construct(ClinicRepository $repository)
    {
        parent::__construct($repository);
    }

    // Add any additional methods here
}
