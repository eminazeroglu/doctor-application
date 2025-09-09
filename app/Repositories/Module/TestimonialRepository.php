<?php

namespace App\Repositories\Module;

use App\Models\Testimonial;
use App\Repositories\BaseRepository;
use App\Services\Filter\TestimonialFilter;

class TestimonialRepository extends BaseRepository
{
    public function __construct(Testimonial $model)
    {
        parent::__construct($model);
        $this->setFilter(new TestimonialFilter(request()));
    }

    // Add any additional methods here
}
