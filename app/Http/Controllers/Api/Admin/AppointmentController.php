<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\ApiController;
use App\Http\Resources\Admin\AppointmentResource;
use App\Services\Filter\AppointmentFilter;
use App\Services\Module\AppointmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppointmentController extends ApiController
{
    public function __construct(AppointmentService $service)
    {
        parent::__construct($service, 'appointment');
        $this->setResource(AppointmentResource::class);
    }

    public function commonRules(): array
    {
        return [
            'doctor_id' => 'required|exists:doctors,id',
            'patient_id' => 'required|exists:patients,id',
            'clinic_id' => 'nullable|exists:clinics,id',
            'service_id' => 'nullable|exists:services,id',
            'start_time' => 'required|date|after:now',
            'end_time' => 'required|date|after:start_time',
            'complaint' => 'nullable|string|max:1000',
            'notes' => 'nullable|string|max:1000',
            'price' => 'nullable|numeric|min:0',
            'consultation_type' => 'required|in:in_person,online,home_visit',
            'location' => 'nullable|string|max:255',
        ];
    }

    /**
     * Yeni randevu yarat
     */
    public function store(Request $request): JsonResponse
    {
        if ($this->authorizeAction('create')) {
            $validatedData = $this->validateRequest($request, $this->storeRules(), $this->storeMessages());

            $appointment = $this->service->create($validatedData);

            return response()->json([
                'data' => $this->toResource($appointment),
                'message' => 'Randevu uğurla yaradıldı'
            ], 201);
        }

        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Randevu yenilə
     */
    public function update(Request $request, $id): JsonResponse
    {
        if ($this->authorizeAction('update')) {
            $validatedData = $this->validateRequest($request, $this->updateRules(), $this->updateMessages());

            $appointment = $this->service->update($id, $validatedData);

            return response()->json([
                'data' => $this->toResource($appointment),
                'message' => 'Randevu uğurla yeniləndi'
            ]);
        }

        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Randevu statusunu dəyişdir
     */
    public function action(Request $request, $id): JsonResponse
    {
        if ($this->authorizeAction('status')) {
            $appointment = $this->service->changeStatus($id, $request->all());

            return response()->json([
                'data' => $this->toResource($appointment),
                'message' => 'Randevu statusu uğurla dəyişdirildi'
            ]);
        }

        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Bu günkü randevular
     */
    public function today(): JsonResponse
    {
        if ($this->authorizeAction('read')) {
            $appointments = $this->service->getTodayAppointments();

            return response()->json([
                'data' => AppointmentResource::collection($appointments),
                'total' => $appointments->count()
            ]);
        }

        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Randevu statistikaları
     */
    public function statistics(Request $request): JsonResponse
    {
        if ($this->authorizeAction('read')) {
            $statistics = $this->service->getStatistics($request->all());

            return response()->json([
                'data' => $statistics
            ]);
        }

        return response()->json(['message' => $this->forbiddenMessage], 403);
    }
}
