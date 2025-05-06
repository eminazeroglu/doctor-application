<?php

namespace App\Services\Module;

use App\Repositories\Module\CommentRepository;
use App\Services\BaseCrudService;

class CommentService extends BaseCrudService
{
    public function __construct(CommentRepository $repository)
    {
        parent::__construct($repository);
    }

    // Add any additional methods here
}
