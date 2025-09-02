<?php

namespace App\Http\Controllers\Api\Front;

use App\Exceptions\BaseException;
use App\Http\Controllers\Controller;
use App\Http\Resources\Front\UserResource;
use App\Services\Module\DoctorCalendarService;
use App\Traits\Controller\HasValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DoctorCalendarController extends Controller
{
    use HasValidatesRequests;

    public function __construct(protected DoctorCalendarService $service) {}

    /**
     * GET /api/doctor/calendar?from=YYYY-MM-DD&to=YYYY-MM-DD
     * Kalendar event-ləri: appointments + recurring schedules + unavailability
     * @throws BaseException
     */
    public function index(Request $request): JsonResponse
    {
        [$from, $to] = $this->service->resolveDateRange(
            $request->query('from'),
            $request->query('to')
        );

        return response()->json($this->service->getCalendarFeed(auth()->id(), $from, $to));
    }

    /**
     * POST /api/doctor/calendar/availability/recurring
     * Recurring availability əlavə edir
     *
     * Body (multipart/form-data və ya json):
     * - start_date (Y-m-d) required
     * - end_date (Y-m-d)   required
     * - from_time (H:i)    required
     * - to_time (H:i)      required
     * - frequency (daily|weekly|monthly) required
     * - every (int)        required
     * - days (array[int 0..6]) weekly üçün (B.e → 1 … Bazar → 0/7)
     * - clinic_id (required) → doctor_clinic-dən biri olmalıdır
     * @throws BaseException|ValidationException
     */
    public function storeRecurring(Request $request): JsonResponse
    {
        $data = $this->validateRequest($request, [
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'from_time'  => 'required|date_format:H:i',
            'to_time'    => 'required|date_format:H:i|after:from_time',
            'frequency'  => 'required|in:daily,weekly,monthly',
            'every'      => 'required|integer|min:1',
            'days'       => 'nullable|array',
            'days.*'     => 'integer|min:0|max:6',
            'clinic_id'  => 'required|exists:clinics,id',
        ]);

        $recurring = $this->service->createRecurringAvailability(auth()->id(), $data);

        return response()->json([
            'recurring' => $recurring,
            'message'   => t('notification.calendar.recurring_created')
        ]);
    }

    /**
     * PUT /api/doctor/calendar/availability/recurring/{id}
     * Recurring availability redaktə edir
     * @throws ValidationException|BaseException
     */
    public function updateRecurring(Request $request, int $id): JsonResponse
    {
        $data = $this->validateRequest($request, [
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'from_time'  => 'required|date_format:H:i',
            'to_time'    => 'required|date_format:H:i|after:from_time',
            'frequency'  => 'required|in:daily,weekly,monthly',
            'every'      => 'required|integer|min:1',
            'days'       => 'nullable|array',
            'days.*'     => 'integer|min:0|max:6',
            'clinic_id'  => 'required|exists:clinics,id',
        ]);

        $recurring = $this->service->updateRecurringAvailability(auth()->id(), $id, $data);

        return response()->json([
            'recurring' => $recurring,
            'message'   => t('notification.calendar.recurring_updated')
        ]);
    }

    /**
     * DELETE /api/doctor/calendar/availability/recurring/{id}
     * @throws BaseException
     */
    public function destroyRecurring(int $id): JsonResponse
    {
        $this->service->deleteRecurringAvailability(auth()->id(), $id);

        return response()->json([
            'message' => t('notification.calendar.recurring_deleted')
        ]);
    }

    /**
     * POST /api/doctor/calendar/unavailability
     * Busy interval əlavə edir
     *
     * Body:
     * - start (Y-m-d H:i) required
     * - end   (Y-m-d H:i) required
     * - note  (nullable|string)
     * @throws ValidationException
     * @throws BaseException
     */
    public function storeUnavailability(Request $request): JsonResponse
    {
        $data = $this->validateRequest($request, [
            'start' => 'required|date',
            'end'   => 'required|date|after:start',
            'note'  => 'nullable|string|max:500'
        ]);

        $busy = $this->service->createUnavailability(auth()->id(), $data);

        return response()->json([
            'unavailability' => $busy,
            'message'        => t('notification.calendar.unavailability_created')
        ]);
    }

    /**
     * DELETE /api/doctor/calendar/unavailability/{id}
     * @throws BaseException
     */
    public function destroyUnavailability(int $id): JsonResponse
    {
        $this->service->deleteUnavailability(auth()->id(), $id);

        return response()->json([
            'message' => t('notification.calendar.unavailability_deleted')
        ]);
    }

    /**
     * PUT /api/doctor/calendar/appointments/{id}/status
     * Appointment statusunu dəyişir (Agenda pop-up)
     *
     * Body: { status: <AppointmentStatusEnum value> }
     * @throws ValidationException
     */
    public function updateAppointmentStatus(Request $request, int $id): JsonResponse
    {
        $data = $this->validateRequest($request, [
            'status' => 'required'
        ]);

        $appointment = $this->service->updateAppointmentStatus(auth()->id(), $id, $data['status']);

        return response()->json([
            'appointment' => $appointment,
            'message'     => t('notification.appointment.status_updated')
        ]);
    }
}
