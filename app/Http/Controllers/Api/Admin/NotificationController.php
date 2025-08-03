<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\ApiController;
use App\Services\Module\NotificationService;

class NotificationController extends ApiController
{
    public function __construct(NotificationService $service)
    {
        parent::__construct($service, 'notification');
    }

    // Add any additional methods here

    public function commonRules(): array
    {
        return [
            // Add validation rules for store method
        ];
    }
}
