<?php

namespace App\Services\Module;

use App\Repositories\Module\TestimonialRepository;
use App\Services\BaseCrudService;

class TestimonialService extends BaseCrudService
{
    public function __construct(TestimonialRepository $repository)
    {
        parent::__construct($repository);
    }

    // Add any additional methods here
}
