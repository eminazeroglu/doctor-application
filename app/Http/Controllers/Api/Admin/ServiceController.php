<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\ApiController;
use App\Services\Module\ServiceService;
use Illuminate\Http\JsonResponse;

class ServiceController extends ApiController
{
    public function __construct(ServiceService $service)
    {
        parent::__construct($service, 'service');
    }

    public function commonRules(): array
    {
        return [
            'category_id' => 'required|exists:categories,id',
            'translates' => 'required|array',
            'translates.az.name' => 'required|string|max:255',
            'translates.az.description' => 'required|string',
            'price' => 'nullable|numeric|min:0',
            'duration' => 'nullable|integer|min:1',
            'is_popular' => 'boolean',
            'is_active' => 'boolean',
            'order' => 'integer',
            'photo' => 'nullable|image|max:2048',
            'meta_tags' => 'nullable|array',
            'custom_fields' => 'nullable|array',
        ];
    }

    /**
     * Kateqoriyaya aid xidmətləri əldə edir
     */
    public function getByCategory(int $categoryId): JsonResponse
    {
        if ($this->authorizeAction('read')) {
            $services = $this->service->findByCategory($categoryId);
            return response()->json([
                'data' => $this->toResource($services)
            ]);
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Populyar xidmətləri əldə edir
     */
    public function getPopular(): JsonResponse
    {
        if ($this->authorizeAction('read')) {
            $services = $this->service->findPopular();
            return response()->json([
                'data' => $this->toResource($services)
            ]);
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Həkimə aid xidmətləri əldə edir
     */
    public function getByDoctor(int $doctorId): JsonResponse
    {
        if ($this->authorizeAction('read')) {
            $services = $this->service->findByDoctor($doctorId);
            return response()->json([
                'data' => $this->toResource($services)
            ]);
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Həkimin xidmətlərini sinxronlaşdırır
     */
    public function syncDoctorServices(Request $request, int $doctorId): JsonResponse
    {
        if ($this->authorizeAction('update')) {
            $this->validateRequest($request, [
                'services' => 'required|array',
                'services.*.service_id' => 'required|exists:medical_services,id',
                'services.*.custom_price' => 'nullable|numeric|min:0',
                'services.*.custom_duration' => 'nullable|integer|min:1',
                'services.*.custom_fields' => 'nullable|array',
            ]);

            $this->service->syncDoctorServices($doctorId, $request->services);

            return response()->json([
                'message' => 'Həkimin xidmətləri uğurla yeniləndi'
            ]);
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Həkimin xidmətlərini əldə edir
     */
    public function getDoctorServices(int $doctorId): JsonResponse
    {
        if ($this->authorizeAction('read')) {
            $data = $this->service->getDoctorServices($doctorId);
            return response()->json([
                'doctor' => $data['doctor'],
                'services' => $this->toResource($data['services'])
            ]);
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }
}
