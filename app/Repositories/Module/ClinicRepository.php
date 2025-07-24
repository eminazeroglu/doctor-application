<?php

namespace App\Repositories\Module;

use App\Models\Clinic;
use App\Repositories\BaseRepository;
use App\Services\Filter\ClinicFilter;

class ClinicRepository extends BaseRepository
{
    public function __construct(Clinic $model)
    {
        parent::__construct($model);
        $this->setFilter(new ClinicFilter(request()));
    }

    // Add any additional methods here
}
