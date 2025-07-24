<?php

namespace App\Repositories\Module;

use App\Models\Appointment;
use App\Repositories\BaseRepository;
use App\Services\Filter\AppointmentFilter;

class AppointmentRepository extends BaseRepository
{
    public function __construct(Appointment $model)
    {
        parent::__construct($model);
        $this->setFilter(new AppointmentFilter(request()));
    }

    // Add any additional methods here
}
