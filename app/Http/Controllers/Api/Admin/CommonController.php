<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\Module\CommonService;

class CommonController extends Controller
{
    protected $service;

    public function __construct(CommonService $service)
    {
        $this->service = $service;
    }

    /**
     * Start
     */
    public function start() {
        return response()->json($this->service->start());
    }
}
