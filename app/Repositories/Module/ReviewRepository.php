<?php

namespace App\Repositories\Module;

use App\Models\Review;
use App\Repositories\BaseRepository;
use App\Services\Filter\ReviewFilter;

class ReviewRepository extends BaseRepository
{
    public function __construct(Review $model)
    {
        parent::__construct($model);
        $this->setFilter(new ReviewFilter(request()));
    }

    // Add any additional methods here
}
