<?php

namespace App\Services\Module;

use App\Repositories\Module\ReviewRepository;
use App\Services\BaseCrudService;

class ReviewService extends BaseCrudService
{
    public function __construct(ReviewRepository $repository)
    {
        parent::__construct($repository);
    }

    // Add any additional methods here
}
