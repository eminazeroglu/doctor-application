<?php

namespace App\Services\Module;

use App\Repositories\Module\PaymentServiceRepository;
use App\Services\BaseCrudService;

class PaymentServiceService extends BaseCrudService
{
    public function __construct(PaymentServiceRepository $repository)
    {
        parent::__construct($repository);
    }

    public function findByIdOptions($id)
    {
        return $this->repository->findByIdOptions($id);
    }

    public function saveOption($id, $data)
    {
        return $this->repository->saveOption($id, $data);
    }

    public function fetchListingService($type)
    {
        return $this->repository->findListByType($type);
    }
}
