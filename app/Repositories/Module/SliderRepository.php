<?php

namespace App\Repositories\Module;

use App\Models\Slider;
use App\Repositories\BaseRepository;
use App\Services\Filter\SliderFilter;

class SliderRepository extends BaseRepository
{
    public function __construct(Slider $model)
    {
        parent::__construct($model);
        $this->setFilter(new SliderFilter(request()));
    }

    // Add any additional methods here
}
