<?php

namespace App\Services\Module;

use App\Repositories\Module\FaqRepository;
use App\Services\BaseCrudService;

class FaqService extends BaseCrudService
{
    public function __construct(FaqRepository $repository)
    {
        parent::__construct($repository);
    }

    // Add any additional methods here
}
