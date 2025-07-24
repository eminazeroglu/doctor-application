<?php

namespace App\Repositories\Module;

use App\Models\Patient;
use App\Repositories\BaseRepository;
use App\Services\Filter\PatientFilter;

class PatientRepository extends BaseRepository
{
    public function __construct(Patient $model)
    {
        parent::__construct($model);
        $this->setFilter(new PatientFilter(request()));
    }

    // Add any additional methods here
}
