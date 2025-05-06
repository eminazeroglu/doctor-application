<?php

namespace App\Repositories\Module;

use App\Models\Subway;
use App\Repositories\BaseRepository;
use App\Services\Filter\SubwayFilter;

class SubwayRepository extends BaseRepository
{
    public function __construct(Subway $model)
    {
        parent::__construct($model);
        $this->setFilter(new SubwayFilter(request()));
    }

    // Add any additional methods here
}
