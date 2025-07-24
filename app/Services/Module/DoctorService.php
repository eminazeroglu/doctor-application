<?php

namespace App\Services\Module;

use App\Repositories\Module\DoctorRepository;
use App\Services\BaseCrudService;

class DoctorService extends BaseCrudService
{
    public function __construct(DoctorRepository $repository)
    {
        parent::__construct($repository);
    }

    // Add any additional methods here
}
