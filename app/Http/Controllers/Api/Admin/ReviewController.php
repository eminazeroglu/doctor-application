<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\ApiController;
use App\Http\Resources\Admin\ReviewResource;
use App\Services\Module\ReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends ApiController
{
    public function __construct(ReviewService $service)
    {
        parent::__construct($service, 'review');
        $this->setResource(ReviewResource::class);
    }

    /**
     * Həkim üzrə rəyləri əldə edir
     */
    public function byDoctor(Request $request, int $doctorId): JsonResponse
    {
        if ($this->authorizeAction('read')) {
            $data = $this->service->getByDoctor($doctorId);
            return response()->json([
                'data' => $this->toResource($data),
                'total' => $data->count()
            ]);
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Klinika üzrə rəyləri əldə edir
     */
    public function byClinic(Request $request, int $clinicId): JsonResponse
    {
        if ($this->authorizeAction('read')) {
            $data = $this->service->getByClinic($clinicId);
            return response()->json([
                'data' => $this->toResource($data),
                'total' => $data->count()
            ]);
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Moderasiya gözləyən rəyləri əldə edir
     */
    public function awaitingModeration(): JsonResponse
    {
        if ($this->authorizeAction('read')) {
            $data = $this->service->getAwaitingModeration();
            return response()->json([
                'data' => $this->toResource($data),
                'total' => $data->count()
            ]);
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Ən faydalı rəyləri əldə edir
     */
    public function mostHelpful(Request $request): JsonResponse
    {
        if ($this->authorizeAction('read')) {
            $limit = $request->get('limit', 10);
            $data = $this->service->getMostHelpful($limit);
            return response()->json([
                'data' => $this->toResource($data),
                'total' => $data->count()
            ]);
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Şikayət edilmiş rəyləri əldə edir
     */
    public function reported(): JsonResponse
    {
        if ($this->authorizeAction('read')) {
            $data = $this->service->getReported();
            return response()->json([
                'data' => $this->toResource($data),
                'total' => $data->count()
            ]);
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Rəy statistikalarını əldə edir
     */
    public function statistics(): JsonResponse
    {
        if ($this->authorizeAction('read')) {
            $data = $this->service->getStatistics();
            return response()->json($data);
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Rəyi təsdiqlə
     */
    public function verify(int $id): JsonResponse
    {
        if ($this->authorizeAction('status')) {
            $result = $this->service->verify($id);
            return response()->json([
                'success' => $result,
                'message' => $result ? 'Rəy təsdiqləndi' : 'Xəta baş verdi'
            ]);
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Rəyi moderasiya et
     */
    public function moderate(int $id): JsonResponse
    {
        if ($this->authorizeAction('status')) {
            $result = $this->service->moderate($id);
            return response()->json([
                'success' => $result,
                'message' => $result ? 'Rəy moderasiya edildi' : 'Xəta baş verdi'
            ]);
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Store əməliyyatı üçün validasiya qaydaları
     */
    public function storeRules(): array
    {
        return [
            'patient_id' => 'required|exists:users,id',
            'doctor_id' => 'nullable|exists:doctors,id|required_without:clinic_id',
            'clinic_id' => 'nullable|exists:clinics,id|required_without:doctor_id',
            'appointment_id' => 'nullable|exists:appointments,id',
            'comment' => 'nullable|string|max:1000',
            'rating' => 'required|integer|between:1,5',
            'is_anonymous' => 'sometimes|boolean',
            'is_verified' => 'sometimes|boolean',
            'is_moderated' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean'
        ];
    }

    /**
     * Update əməliyyatı üçün validasiya qaydaları
     */
    public function updateRules(): array
    {
        return [
            'patient_id' => 'sometimes|exists:users,id',
            'doctor_id' => 'nullable|exists:doctors,id',
            'clinic_id' => 'nullable|exists:clinics,id',
            'appointment_id' => 'nullable|exists:appointments,id',
            'comment' => 'nullable|string|max:1000',
            'rating' => 'sometimes|integer|between:1,5',
            'is_anonymous' => 'sometimes|boolean',
            'is_verified' => 'sometimes|boolean',
            'is_moderated' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean'
        ];
    }

    /**
     * Validasiya mesajları
     */
    public function commonMessages(): array
    {
        return [
            'patient_id.required' => 'Xəstə seçimi tələb olunur',
            'patient_id.exists' => 'Seçilən xəstə mövcud deyil',
            'doctor_id.exists' => 'Seçilən həkim mövcud deyil',
            'doctor_id.required_without' => 'Həkim və ya klinika seçimi tələb olunur',
            'clinic_id.exists' => 'Seçilən klinika mövcud deyil',
            'clinic_id.required_without' => 'Klinika və ya həkim seçimi tələb olunur',
            'appointment_id.exists' => 'Seçilən randevu mövcud deyil',
            'comment.max' => 'Şərh maksimum 1000 simvol ola bilər',
            'rating.required' => 'Qiymətləndirmə tələb olunur',
            'rating.integer' => 'Qiymətləndirmə rəqəm olmalıdır',
            'rating.between' => 'Qiymətləndirmə 1-5 arasında olmalıdır'
        ];
    }
}
