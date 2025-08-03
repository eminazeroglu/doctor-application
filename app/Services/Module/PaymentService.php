<?php

namespace App\Services\Module;

use App\Repositories\Module\PaymentRepository;
use App\Services\BaseCrudService;

class PaymentService extends BaseCrudService
{
    public function __construct(PaymentRepository $repository)
    {
        parent::__construct($repository);
    }

    // Add any additional methods here
}
