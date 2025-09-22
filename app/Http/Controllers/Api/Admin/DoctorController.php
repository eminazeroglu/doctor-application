<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\ApiController;
use App\Http\Resources\Admin\DoctorResource;
use App\Services\Module\DoctorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DoctorController extends ApiController
{
    public function __construct(DoctorService $service)
    {
        parent::__construct($service, 'doctor');
        $this->setResource(DoctorResource::class);
        $this->setHasShowResource(true);
    }

    public function commonRules(): array
    {
        return [
            // Add validation rules for store method
        ];
    }

    /**
     * Həkimin təsdiq statusunu dəyişir
     */
    public function toggleVerification(Request $request, $id): JsonResponse
    {
        if ($this->authorizeAction('status')) {
            $doctor = $this->service->toggleVerification($id);
            return response()->json($this->toResource($doctor));
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Həkimin populyar statusunu dəyişir
     */
    public function toggleFeatured(Request $request, $id): JsonResponse
    {
        if ($this->authorizeAction('status')) {
            $doctor = $this->service->toggleFeatured($id);
            return response()->json($this->toResource($doctor));
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * İxtisasa görə həkimləri gətirir
     */
    public function getByCategory(Request $request, $categoryId): JsonResponse
    {
        if ($this->authorizeAction('read')) {
            $doctors = $this->service->getDoctorsByCategory($categoryId);
            return response()->json($this->toResource($doctors));
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Klinikaya görə həkimləri gətirir
     */
    public function getByClinic(Request $request, $clinicId): JsonResponse
    {
        if ($this->authorizeAction('read')) {
            $doctors = $this->service->getDoctorsByClinic($clinicId);
            return response()->json($this->toResource($doctors));
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Həkimin məşğulluğunu əlavə edir
     */
    public function addUnavailability(Request $request, $id): JsonResponse
    {
        if ($this->authorizeAction('update')) {
            $this->validateRequest($request, [
                'start_datetime' => 'required|date',
                'end_datetime' => 'required|date|after:start_datetime',
                'clinic_id' => 'nullable|exists:clinics,id',
                'reason' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'is_recurring' => 'boolean',
                'recurring_pattern' => 'nullable|string'
            ]);

            $this->service->addUnavailability($id, $request->all());
            return response()->json(['message' => 'Məşğulluq uğurla əlavə edildi']);
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Həkimin mövcudluğunu yoxlayır
     */
    public function checkAvailability(Request $request, $id): JsonResponse
    {
        if ($this->authorizeAction('read')) {
            $this->validateRequest($request, [
                'date' => 'required|date',
                'time' => 'required|string'
            ]);

            $isAvailable = $this->service->checkDoctorAvailability(
                $id,
                $request->get('date'),
                $request->get('time')
            );

            return response()->json(['available' => $isAvailable]);
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }
}
