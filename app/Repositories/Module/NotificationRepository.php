<?php

namespace App\Repositories\Module;

use App\Models\Notification;
use App\Repositories\BaseRepository;
use App\Services\Filter\NotificationFilter;

class NotificationRepository extends BaseRepository
{
    public function __construct(Notification $model)
    {
        parent::__construct($model);
        $this->setFilter(new NotificationFilter(request()));
    }

    // Add any additional methods here
}
