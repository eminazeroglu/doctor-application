<?php

namespace App\Http\Controllers\Api\Front;

use App\Exceptions\BaseException;
use App\Http\Controllers\Controller;
use App\Http\Resources\Front\AppointmentResource;
use App\Services\Module\DoctorAppointmentService;
use App\Traits\Controller\HasValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DoctorAppointmentController extends Controller
{
    use HasValidatesRequests;

    public function __construct(protected DoctorAppointmentService $service) {}

    /**
     * GET /api/doctor/appointments
     * Query:
     *  - page, per_page
     *  - search (patient name)
     *  - status (multi: comma-separated)
     *  - clinic_id
     *  - from, to (Y-m-d)
     *  - sort (start_time|-start_time)
     * @throws ValidationException
     */
    public function index(Request $request): JsonResponse
    {
        $data = $this->validateRequest($request, [
            'per_page'  => 'nullable|integer|min:1|max:100',
            'search'    => 'nullable|string|max:100',
            'status'    => 'nullable|string', // "pending,confirmed,completed"
            'clinic_id' => 'nullable|integer|exists:clinics,id',
            'from'      => 'nullable|date',
            'to'        => 'nullable|date|after_or_equal:from',
            'sort'      => ['nullable', Rule::in(['start_time', '-start_time'])],
        ]);

        $result = $this->service->list(auth()->id(), $data);

        return response()->json([
            'items' => AppointmentResource::collection($result),
            'meta'  => [
                'current_page' => $result->currentPage(),
                'per_page'     => $result->perPage(),
                'total'        => $result->total(),
                'last_page'    => $result->lastPage(),
            ]
        ]);
    }

    /**
     * GET /api/doctor/appointments/{id}
     */
    public function show(int $id): JsonResponse
    {
        $appointment = $this->service->getOne(auth()->id(), $id);
        return response()->json(new AppointmentResource($appointment));
    }

    /**
     * PUT /api/doctor/appointments/{id}/status
     * Body: { status: <enum> }
     * @throws ValidationException|BaseException
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $data = $this->validateRequest($request, [
            'status' => ['required', Rule::in([
                'pending','confirmed','cancelled','completed','no-show','rescheduled',
            ])],
        ]);

        $appointment = $this->service->updateStatus(auth()->id(), $id, $data['status']);

        return response()->json([
            'appointment' => new AppointmentResource($appointment),
            'message'     => t('notification.appointment.status_updated')
        ]);
    }

    /**
     * PUT /api/doctor/appointments/{id}/reschedule
     * Body: { start: "Y-m-d H:i", end: "Y-m-d H:i", clinic_id?, service_id? }
     * @throws ValidationException|BaseException
     */
    public function reschedule(Request $request, int $id): JsonResponse
    {
        $data = $this->validateRequest($request, [
            'start'     => 'required|date',
            'end'       => 'required|date|after:start',
            'clinic_id' => 'nullable|integer|exists:clinics,id',
            'service_id'=> 'nullable|integer|exists:services,id',
        ]);

        $appointment = $this->service->reschedule(auth()->id(), $id, $data);

        return response()->json([
            'appointment' => new AppointmentResource($appointment),
            'message'     => t('notification.appointment.rescheduled')
        ]);
    }

    /**
     * PUT /api/doctor/appointments/{id}/note
     * Body: { note: string|null }
     * @throws ValidationException
     */
    public function updateNote(Request $request, int $id): JsonResponse
    {
        $data = $this->validateRequest($request, [
            'note' => 'required|string|max:1000'
        ]);

        $appointment = $this->service->updateNote(auth()->id(), $id, $data['note'] ?? null);

        return response()->json([
            'appointment' => new AppointmentResource($appointment),
            'message'     => t('notification.appointment.note_updated')
        ]);
    }
}
