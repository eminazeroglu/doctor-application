<?php

namespace App\Services\Module;

use App\Repositories\Module\LanguageRepository;
use App\Services\BaseCrudService;

class LanguageService extends BaseCrudService
{
    public function __construct(LanguageRepository $repository)
    {
        parent::__construct($repository);
    }
}
