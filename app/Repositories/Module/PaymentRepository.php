<?php

namespace App\Repositories\Module;

use App\Models\Payment;
use App\Repositories\BaseRepository;
use App\Services\Filter\PaymentFilter;

class PaymentRepository extends BaseRepository
{
    public function __construct(Payment $model)
    {
        parent::__construct($model);
        $this->setFilter(new PaymentFilter(request()));
    }

    // Add any additional methods here
}
