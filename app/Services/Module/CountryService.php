<?php

namespace App\Services\Module;

use App\Repositories\Module\CountryRepository;
use App\Services\BaseCrudService;

class CountryService extends BaseCrudService
{
    public function __construct(CountryRepository $repository)
    {
        parent::__construct($repository);
    }

    // Add any additional methods here
}
