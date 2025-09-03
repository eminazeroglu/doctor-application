<?php

namespace App\Http\Controllers\Api\Front;

use App\Exceptions\BaseException;
use App\Http\Controllers\Controller;
use App\Http\Resources\Front\AppointmentResource;
use App\Services\Module\AppointmentService;
use App\Services\Module\DoctorAppointmentService;
use App\Traits\Controller\HasValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

    /**
     * GET /api/doctor/appointments/report
     * Appointment report (stats + list)
     * @throws ValidationException
     */
    public function report(Request $request): JsonResponse
    {
        $data = $this->validateRequest($request, [
            'from'        => 'nullable|date',
            'to'          => 'nullable|date|after_or_equal:from',
            'clinic_id'   => 'nullable|integer|exists:clinics,id',
            'status'      => 'nullable|string',
            'per_page'    => 'nullable|integer|min:1|max:100',
        ]);

        $result = $this->service->report(auth()->id(), $data);

        return response()->json($result);
    }

    /**
     * DELETE /api/app/appointments/{uuid}
     * @throws BaseException
     * @throws ValidationException
     */
    public function cancel(Request $request, string $uuid): JsonResponse
    {
        $this->validateRequest(
            $request,
            [
                'reasons' => 'required|array|min:1',
                'reasons.*' => 'string|in:doctor_dislike,time_conflict,found_another_doctor,personal_reason,other',
                'custom_reason' => 'nullable|string|max:500',
                'note' => 'nullable|string|max:1000'
            ],
            [
                'reasons.required' => 'Ləğv səbəbi seçilməlidir',
                'reasons.min' => 'Ən azı bir səbəb seçilməlidir'
            ]
        );


        $doctor = Auth::user()->doctor;

        if (!$doctor) {
            throw new BaseException(['message' => 'Xəstə profili tapılmadı'], 404);
        }

        $appointment = app(AppointmentService::class)->getAppointmentByUuid($uuid, $doctor->id, 'doctor');

        if (!$appointment) {
            throw new BaseException(['message' => 'Randevu tapılmadı'], 404);
        }

        // Randevu ləğv edilə bilər mi yoxlanılır
        if (!app(AppointmentService::class)->canCancel($appointment)) {
            throw new BaseException([
                'message' => 'Bu randevu artıq ləğv edilə bilməz'
            ], 422);
        }

        $cancelData = [
            'reasons' => $request->reasons,
            'custom_reason' => $request->custom_reason,
            'note' => $request->note,
            'cancelled_by' => 'doctor'
        ];

        $cancelledAppointment = app(AppointmentService::class)->cancelAppointment(
            $appointment,
            $cancelData
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Randevu uğurla ləğv edildi',
            'data' => new AppointmentResource($cancelledAppointment)
        ]);


    }
}
