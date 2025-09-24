<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\ApiController;
use App\Http\Resources\Admin\ClinicResource;
use App\Services\Module\ClinicService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ClinicController extends ApiController
{
    public string $resourceClass = ClinicResource::class;

    public function __construct(ClinicService $service)
    {
        parent::__construct($service, 'clinic');
        $this->setResource(ClinicResource::class);
    }

    /**
     * Yaxınlıqdakı klinikalar
     */
    public function nearby(Request $request): JsonResponse
    {
        if ($this->authorizeAction('read')) {
            $request->validate([
                'latitude' => 'required|numeric|between:-90,90',
                'longitude' => 'required|numeric|between:-180,180',
                'radius' => 'nullable|integer|min:1|max:100'
            ]);

            $clinics = $this->service->findNearby(
                $request->latitude,
                $request->longitude,
                $request->radius ?? 10
            );

            return response()->json([
                'data' => $this->toResource($clinics)
            ]);
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Populyar klinikalar
     */
    public function popular(Request $request): JsonResponse
    {
        if ($this->authorizeAction('read')) {
            $limit = $request->integer('limit', 10);
            $clinics = $this->service->getPopularClinics($limit);

            return response()->json([
                'data' => $this->toResource($clinics)
            ]);
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Klinika üçün mövcud vaxtlar
     */
    public function availableSlots(Request $request, int $id): JsonResponse
    {
        if ($this->authorizeAction('read')) {
            $request->validate([
                'date' => 'required|date|after_or_equal:today',
                'doctor_id' => 'nullable|integer|exists:doctors,id'
            ]);

            $slots = $this->service->getAvailableSlots(
                $id,
                $request->date,
                $request->doctor_id
            );

            return response()->json([
                'data' => $slots,
                'date' => $request->date,
                'total_slots' => count($slots),
                'available_slots' => count(array_filter($slots, fn($slot) => $slot['is_available']))
            ]);
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Klinika verification status dəyişdirmə
     */
    public function toggleVerified(Request $request, int $id): JsonResponse
    {
        if ($this->authorizeAction('status')) {
            $clinic = $this->service->changeStatus($id, 'is_verified');

            return response()->json([
                'data' => $this->toResource($clinic),
                'message' => 'Klinika verification statusu dəyişdirildi'
            ]);
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Klinika featured status dəyişdirmə
     */
    public function toggleFeatured(Request $request, int $id): JsonResponse
    {
        if ($this->authorizeAction('status')) {
            $clinic = $this->service->changeStatus($id, 'is_featured');

            return response()->json([
                'data' => $this->toResource($clinic),
                'message' => 'Klinika featured statusu dəyişdirildi'
            ]);
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    public function commonRules(): array
    {
        return [
            'translates' => ['required', 'array'],
            'translates.*.name' => ['required', 'string', 'max:255'],
            'translates.*.description' => ['nullable', 'string'],
            'translates.*.address' => ['nullable', 'string'],

            // Əlaqə məlumatları
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255|unique:clinics,email,' . request()->id,
            'website' => 'nullable|url|max:255',

            // Ünvan məlumatları
            'address' => 'nullable|string|max:500',
            'country_id' => 'required|exists:cities,id',
            'city_id' => 'required|exists:cities,id',
            'region_id' => 'nullable|exists:regions,id',
            'postal_code' => 'nullable|string|max:10',

            // Koordinatlar
            'location' => ['nullable', 'array'],
            'location.lat' => ['nullable', 'numeric', 'between:-90,90'],
            'location.lng' => ['nullable', 'numeric', 'between:-180,180'],

            // Media
            'logo_path' => 'nullable|string',
            'gallery' => 'nullable|array',
            'gallery.*' => 'string',

            // İş saatları
            'working_hours' => 'nullable|array',
            'working_hours.*.day_of_week' => 'required|string|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'working_hours.*.open_time' => 'required_if:working_hours.*.is_closed,false|nullable|date_format:H:i',
            'working_hours.*.close_time' => 'required_if:working_hours.*.is_closed,false|nullable|date_format:H:i|after:working_hours.*.open_time',
            'working_hours.*.is_closed' => 'boolean',
            'working_hours.*.note' => 'nullable|string|max:255',

            // İmkanlar
            'facilities' => 'nullable|array',

            // Status sahələri
            'is_verified' => 'boolean',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'parent_id' => 'nullable|exists:clinics,id',

            // Əlaqələr
            'categories' => 'required|array',
            'categories.*' => 'exists:categories,id',

            'services' => 'nullable|array',
            'services.*.service_id' => 'required|exists:services,id',
            'services.*.price' => 'nullable|numeric|min:0',
            'services.*.duration' => 'nullable|integer|min:1',
            'services.*.description' => 'nullable|string',
            'services.*.is_active' => 'boolean',

            // Əlavə sahələr
            'custom_fields' => 'nullable|json',
        ];
    }

    public function commonMessages(): array
    {
        return [

            'email.unique' => 'Bu email artıq istifadə olunur',
            'phone.max' => 'Telefon nömrəsi çox uzundur',
            'latitude.between' => 'Enlik dərəcəsi -90 ilə 90 arasında olmalıdır',
            'longitude.between' => 'Uzunluq dərəcəsi -180 ilə 180 arasında olmalıdır',
            'working_hours.*.open_time.required_if' => 'Açılış saatı tələb olunur',
            'working_hours.*.close_time.required_if' => 'Bağlanma saatı tələb olunur',
            'working_hours.*.close_time.after' => 'Bağlanma saatı açılış saatından sonra olmalıdır',
            'categories.*.exists' => 'Seçilmiş kateqoriya mövcud deyil',
            'services.*.service_id.exists' => 'Seçilmiş xidmət mövcud deyil',
            'services.*.price.min' => 'Qiymət mənfi ola bilməz',
            'services.*.duration.min' => 'Müddət minimum 1 dəqiqə olmalıdır',
        ];
    }

    /**
     * Klinika statistikaları
     */
    public function statistics(Request $request, int $id): JsonResponse
    {
        if ($this->authorizeAction('read')) {
            $statistics = $this->service->getStatistics($id);
            return response()->json($statistics);
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }
}
