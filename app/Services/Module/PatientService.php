<?php

namespace App\Services\Module;

use App\Repositories\Module\PatientRepository;
use App\Services\BaseCrudService;

class PatientService extends BaseCrudService
{
    public function __construct(PatientRepository $repository)
    {
        parent::__construct($repository);
    }

    // Add any additional methods here
}
