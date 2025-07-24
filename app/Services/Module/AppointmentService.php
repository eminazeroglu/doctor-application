<?php

namespace App\Services\Module;

use App\Repositories\Module\AppointmentRepository;
use App\Services\BaseCrudService;

class AppointmentService extends BaseCrudService
{
    public function __construct(AppointmentRepository $repository)
    {
        parent::__construct($repository);
    }

    // Add any additional methods here
}
