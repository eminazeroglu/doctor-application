<?php

namespace App\Services\Module;

use App\Repositories\Module\BlogRepository;
use App\Services\BaseCrudService;

class BlogService extends BaseCrudService
{
    public function __construct(BlogRepository $repository)
    {
        parent::__construct($repository);
    }

    public function blogSearch() {
        return $this->repository->blogSearch();
    }

    public function blogView($slug) {
        return $this->repository->blogView($slug);
    }

    public function blogLast($blogId) {
        return $this->repository->blogLast($blogId);
    }
}
