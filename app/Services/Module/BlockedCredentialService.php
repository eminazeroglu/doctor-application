<?php

namespace App\Services\Module;

use App\Repositories\Module\BlockedCredentialRepository;
use App\Services\BaseCrudService;

class BlockedCredentialService extends BaseCrudService
{
    public function __construct(BlockedCredentialRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Bloklanmış credential-ın məlumatlarını almaq
     */
    public function getBlockInfo(string $type, string $value): ?object
    {
        return $this->repository->findActiveByCredential($type, $value);
    }
}
