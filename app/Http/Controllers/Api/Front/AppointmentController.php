<?php

namespace App\Http\Controllers\Api\Front;

use App\Enums\AppointmentStatusEnum;
use App\Exceptions\BaseException;
use App\Http\Controllers\Controller;
use App\Http\Resources\Front\AppointmentResource;
use App\Services\Module\AppointmentService;
use App\Services\Module\ReviewService;
use App\Traits\Controller\HasValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AppointmentExport;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AppointmentController extends Controller
{
    use HasValidatesRequests;

    protected AppointmentService $appointmentService;
    protected ReviewService $reviewService;

    public function __construct(
        AppointmentService $appointmentService,
        ReviewService      $reviewService
    )
    {
        $this->appointmentService = $appointmentService;
        $this->reviewService = $reviewService;
    }

    /**
     * Screen 1: Xəstənin randevuları siyahısı
     * GET /api/app/appointments
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $patient = Auth::user()->patient;

            if (!$patient) {
                throw new BaseException('Xəstə profili tapılmadı', 404);
            }

            // Filterlər
            $filters = [
                'patient_id' => $patient->id,
                'status' => $request->get('status'),
                'date_range' => $request->get('date_range', 'last_six_months'),
                'per_page' => $request->get('per_page', 15),
                'sort' => $request->get('sort', 'start_time'),
                'direction' => $request->get('direction', 'desc')
            ];

            $appointments = $this->appointmentService->getPatientAppointments($filters);

            return response()->json([
                'status' => 'success',
                'data' => AppointmentResource::collection($appointments->items()),
                'meta' => [
                    'current_page' => $appointments->currentPage(),
                    'last_page' => $appointments->lastPage(),
                    'per_page' => $appointments->perPage(),
                    'total' => $appointments->total(),
                    'from' => $appointments->firstItem(),
                    'to' => $appointments->lastItem(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Filter seçimlərini qaytarır
     * GET /api/app/appointments/filters
     */
    public function getFilters(): JsonResponse
    {
        try {
            $filters = $this->appointmentService->getFilters();

            return response()->json([
                'status' => 'success',
                'data' => $filters
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Randevu statistikaları
     * GET /api/app/appointments/stats
     */
    public function stats(): JsonResponse
    {
        try {
            $patient = Auth::user()->patient;

            if (!$patient) {
                throw new BaseException(['message' => 'Xəstə profili tapılmadı'], 404);
            }

            $stats = $this->appointmentService->getPatientStats($patient->id);

            return response()->json([
                'status' => 'success',
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Screen 1: Excel export
     * GET /api/app/appointments/export
     */
    public function export(Request $request): BinaryFileResponse|JsonResponse
    {
        try {
            $patient = Auth::user()->patient;

            if (!$patient) {
                throw new BaseException(['message' => 'Xəstə profili tapılmadı'], 404);
            }

            $filters = [
                'patient_id' => $patient->id,
                'status' => $request->get('status'),
                'date_range' => $request->get('date_range', 'last_six_months')
            ];

            $fileName = 'randevular_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

            return Excel::download(
                new AppointmentExport($filters),
                $fileName
            );

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Randevu detalları
     * GET /api/app/appointments/{uuid}
     */
    public function show(string $uuid): JsonResponse
    {
        try {
            $patient = Auth::user()->patient;

            if (!$patient) {
                throw new BaseException(['message' => 'Xəstə profili tapılmadı'], 404);
            }

            $appointment = $this->appointmentService->getAppointmentByUuid($uuid, $patient->id);

            if (!$appointment) {
                throw new BaseException(['message' => 'Randevu tapılmadı'], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => new AppointmentResource($appointment)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], $e->getCode() ?: 500);
        }
    }

    /**
     * Screen 2: Randevu ləğvi
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


        $patient = Auth::user()->patient;

        if (!$patient) {
            throw new BaseException(['message' => 'Xəstə profili tapılmadı'], 404);
        }

        $appointment = $this->appointmentService->getAppointmentByUuid($uuid, $patient->id);

        if (!$appointment) {
            throw new BaseException(['message' => 'Randevu tapılmadı'], 404);
        }

        // Randevu ləğv edilə bilər mi yoxlanılır
        if (!$this->appointmentService->canCancel($appointment)) {
            throw new BaseException([
                'message' => 'Bu randevu artıq ləğv edilə bilməz'
            ], 422);
        }

        $cancelData = [
            'reasons' => $request->reasons,
            'custom_reason' => $request->custom_reason,
            'note' => $request->note,
            'cancelled_by' => 'patient'
        ];

        $cancelledAppointment = $this->appointmentService->cancelAppointment(
            $appointment,
            $cancelData
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Randevu uğurla ləğv edildi',
            'data' => new AppointmentResource($cancelledAppointment)
        ]);


    }

    /**
     * Screen 3: Rəy yazma
     * POST /api/app/appointments/{uuid}/review
     * @throws BaseException
     */
    public function createReview(Request $request, string $uuid): JsonResponse
    {

        $this->validateRequest(
            $request,
            [
                'rating' => 'required|integer|min:1|max:5',
                'comment' => 'nullable|string|max:1000',
                'is_anonymous' => 'nullable|boolean'
            ],
            [
                'rating.required' => 'Qiymətləndirmə mütləqdir',
                'rating.min' => 'Ən az 1 ulduz verilməlidir',
                'rating.max' => 'Maksimum 5 ulduz verilə bilər',
                'comment.max' => 'Rəy 1000 simvoldan çox ola bilməz'
            ]
        );

        $patient = Auth::user()->patient;

        if (!$patient) {
            throw new BaseException(['message' => 'Xəstə profili tapılmadı'], 404);
        }

        $appointment = $this->appointmentService->getAppointmentByUuid($uuid, $patient->id);

        if (!$appointment) {
            throw new BaseException(['message' => 'Randevu tapılmadı'], 404);
        }

        // Rəy yazıla bilər mi yoxlanılır
        if (!$this->appointmentService->canReview($appointment)) {
            throw new BaseException([
                'message' => 'Bu randevu üçün rəy yazıla bilməz'
            ], 422);
        }

        $reviewData = [
            'patient_id' => $patient->id,
            'appointment_id' => $appointment->id,
            'doctor_id' => $appointment->doctor_id,
            'clinic_id' => $appointment->clinic_id,
            'rating' => $request->rating,
            'comment' => $request->comment,
            'is_anonymous' => $request->boolean('is_anonymous', false)
        ];

        $review = $this->reviewService->createReview($reviewData);

        return response()->json([
            'status' => 'success',
            'message' => 'Rəyiniz uğurla göndərildi',
            'data' => [
                'uuid' => $review->uuid,
                'rating' => $review->rating,
                'comment' => $review->comment,
                'is_anonymous' => $review->is_anonymous,
                'created_at' => $review->created_at
            ]
        ], 201);
    }
}
