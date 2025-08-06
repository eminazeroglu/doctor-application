<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\ApiController;
use App\Services\Module\BlogService;

class BlogController extends ApiController
{
    public function __construct(BlogService $service)
    {
        parent::__construct($service, 'blog');
    }

    // Add any additional methods here

    public function commonRules(): array
    {
        return [
            // Add validation rules for store method
        ];
    }
}
