<?php

namespace App\Services\Module;

use App\Repositories\Module\AdvertisementRepository;
use App\Services\BaseCrudService;

class AdvertisementService extends BaseCrudService
{
    public function __construct(AdvertisementRepository $repository)
    {
        parent::__construct($repository);
    }

    // Add any additional methods here
}
