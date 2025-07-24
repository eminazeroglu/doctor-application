<?php

namespace App\Repositories\Module;

use App\Models\Doctor;
use App\Repositories\BaseRepository;
use App\Services\Filter\DoctorFilter;

class DoctorRepository extends BaseRepository
{
    public function __construct(Doctor $model)
    {
        parent::__construct($model);
        $this->setFilter(new DoctorFilter(request()));
    }

    // Add any additional methods here
}
