<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\ApiController;
use App\Http\Resources\Admin\BaseResource;
use App\Services\Module\MonitoringService;

class MonitoringController extends ApiController
{
    public function __construct(MonitoringService $service)
    {
        // admin istifadəçiləri üçün monitoring icazəsi
        parent::__construct($service, 'monitoring');
    }

    public function getStats()
    {
        // Ümumi statistika
        return response()->json($this->service->getStats());
    }

    public function getQueries()
    {
        // Query statistikaları və yavaş sorğular
        return response()->json(BaseResource::collection($this->service->getQueries()));
    }

    public function getExceptions()
    {
        // Sistem xətaları
        return response()->json($this->service->getExceptions());
    }

    public function getRequests()
    {
        // HTTP sorğuları və statusları
        return response()->json($this->service->getRequests());
    }

    public function getModels()
    {
        // Model əməliyyatları
        return response()->json($this->service->getModels());
    }
}
