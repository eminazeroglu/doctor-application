<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Common\BlockCredentialRequest;
use App\Services\Module\BlockedCredentialService;

class BlockedCredentialController extends ApiController
{
    public function __construct(BlockedCredentialService $service)
    {
        parent::__construct($service, 'blocked_credential');
        $this->formRequestClass = BlockCredentialRequest::class;
    }

    // Add any additional methods here
}
