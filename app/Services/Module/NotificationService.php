<?php

namespace App\Services\Module;

use App\Repositories\Module\NotificationRepository;
use App\Services\BaseCrudService;

class NotificationService extends BaseCrudService
{
    public function __construct(NotificationRepository $repository)
    {
        parent::__construct($repository);
    }

    // Add any additional methods here
}
