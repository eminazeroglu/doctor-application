<?php

namespace App\Services\Module;

use App\Enums\AppointmentStatusEnum;
use App\Models\Clinic;
use App\Repositories\Module\ClinicRepository;
use App\Services\BaseCrudService;
use Illuminate\Database\Eloquent\Model;

class ClinicService extends BaseCrudService
{
    public function __construct(ClinicRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Klinika yaradır
     */
    public function create(array $data): Model
    {
        // Working hours-u əgər göndərilibsə, ayırırıq
        $workingHours = $data['working_hours'] ?? null;
        unset($data['working_hours']);

        // Categories-i ayırırıq
        $categories = $data['categories'] ?? [];
        unset($data['categories']);

        // Services-i ayırırıq
        $services = $data['services'] ?? [];
        unset($data['services']);

        $clinic = $this->repository->create($data);

        // Əlaqələri sync edirik
        $this->syncRelations($clinic, [
            'categories' => $categories,
            'services' => $services,
            'working_hours' => $workingHours
        ]);

        return $clinic->load(['categories', 'services', 'workingHours', 'city', 'region']);
    }

    /**
     * Klinika yeniləyir
     */
    public function update(int $id, array $data): Model
    {
        // Working hours-u əgər göndərilibsə, ayırırıq
        $workingHours = $data['working_hours'] ?? null;
        unset($data['working_hours']);

        // Categories-i ayırırıq
        $categories = $data['categories'] ?? null;
        unset($data['categories']);

        // Services-i ayırırıq
        $services = $data['services'] ?? null;
        unset($data['services']);

        $clinic = $this->repository->update($id, $data);

        // Əlaqələri sync edirik (yalnız göndərilibsə)
        $relations = [];
        if ($categories !== null) $relations['categories'] = $categories;
        if ($services !== null) $relations['services'] = $services;
        if ($workingHours !== null) $relations['working_hours'] = $workingHours;

        if (!empty($relations)) {
            $this->syncRelations($clinic, $relations);
        }

        return $clinic->load(['categories', 'services', 'workingHours', 'city', 'region']);
    }

    /**
     * Klinika silir
     * @throws \Exception
     */
    public function delete(int $id): bool
    {
        $clinic = $this->repository->findById($id);

        // Əgər klinikada aktiv randevular varsa, silməyə icazə vermirik
        $activeAppointments = $clinic->appointments()
            ->whereIn('appointment_status', [AppointmentStatusEnum::Pending, AppointmentStatusEnum::Confirmed])
            ->where('start_time', '>=', now())
            ->count();

        if ($activeAppointments > 0) {
            throw new \Exception('Bu klinikada aktiv randevular ('.$activeAppointments.' ədəd) var. Əvvəlcə onları tamamlayın və ya ləğv edin.');
        }

        return $this->repository->delete($id);
    }

    /**
     * Klinika statusunu dəyişir
     */
    public function changeStatus(int $id, string $statusField = 'is_active'): Model
    {
        $clinic = $this->repository->changeStatus($id, $statusField);

        // Əgər klinika deaktiv edilibsə, həkimlərin də statusunu yoxlayırıq
        if ($statusField === 'is_active' && !$clinic->is_active) {
            $this->handleClinicDeactivation($clinic);
        }

        return $clinic;
    }

    /**
     * Yaxınlıqdakı klinikalar
     */
    public function findNearby(float $latitude, float $longitude, int $radius = 10): Collection
    {
        return $this->repository->findNearby($latitude, $longitude, $radius);
    }

    /**
     * Populyar klinikalar
     */
    public function getPopularClinics(int $limit = 10): Collection
    {
        return $this->repository->getPopularClinics($limit);
    }

    /**
     * Klinika statistikaları
     */
    public function getStatistics(int $id): array
    {
        return $this->repository->getStatistics($id);
    }

    /**
     * Filters
     */
    public function filters(): array
    {
        return $this->repository->filters();
    }

    /**
     * Klinika üçün mövcud vaxtları əldə edir
     */
    public function getAvailableSlots(int $clinicId, string $date, ?int $doctorId = null): array
    {
        $clinic = $this->repository->findById($clinicId);

        // Həftənin günü
        $dayOfWeek = \Carbon\Carbon::parse($date)->format('l');

        // Klinikanın o günə aid iş saatları
        $workingHour = $clinic->workingHours()
            ->where('day_of_week', $dayOfWeek)
            ->first();

        if (!$workingHour || $workingHour->is_closed) {
            return [];
        }

        // Saatları yaradırıq (30 dəqiqə intervallarla)
        $slots = [];
        $start = \Carbon\Carbon::parse($date . ' ' . $workingHour->open_time);
        $end = \Carbon\Carbon::parse($date . ' ' . $workingHour->close_time);

        while ($start->lt($end)) {
            // Bu vaxtda randevu varmı yoxlayırıq
            $isBooked = $clinic->appointments()
                ->where('appointment_date', $date)
                ->where('appointment_time', $start->format('H:i'))
                ->when($doctorId, function($query) use ($doctorId) {
                    return $query->where('doctor_id', $doctorId);
                })
                ->whereIn('status', ['pending', 'confirmed'])
                ->exists();

            $slots[] = [
                'time' => $start->format('H:i'),
                'is_available' => !$isBooked,
                'formatted_time' => $start->format('H:i')
            ];

            $start->addMinutes(30);
        }

        return $slots;
    }

    /**
     * Əlaqələri sync edir
     */
    private function syncRelations(Clinic $clinic, array $relations): void
    {
        // Categories sync
        if (isset($relations['categories'])) {
            $clinic->categories()->sync($relations['categories']);
        }

        // Services sync
        if (isset($relations['services'])) {
            $servicesData = [];
            foreach ($relations['services'] as $service) {
                $servicesData[$service['service_id']] = [
                    'price' => $service['price'] ?? null,
                    'duration' => $service['duration'] ?? null,
                    'description' => $service['description'] ?? null,
                    'is_active' => $service['is_active'] ?? true,
                ];
            }
            $clinic->services()->sync($servicesData);
        }

        // Working hours update
        if (isset($relations['working_hours'])) {
            foreach ($relations['working_hours'] as $dayData) {
                $clinic->workingHours()->updateOrCreate(
                    ['day_of_week' => $dayData['day_of_week']],
                    [
                        'open_time' => $dayData['is_closed'] ? null : $dayData['open_time'],
                        'close_time' => $dayData['is_closed'] ? null : $dayData['close_time'],
                        'is_closed' => $dayData['is_closed'] ?? false,
                        'note' => $dayData['note'] ?? null,
                    ]
                );
            }
        }
    }

    /**
     * Klinika deaktiv edildikdə lazımi əməliyyatlar
     */
    private function handleClinicDeactivation(Clinic $clinic): void
    {
        // Gələcək randevuları ləğv edirik
        $clinic->appointments()
            ->whereIn('appointment_status', [AppointmentStatusEnum::Pending, AppointmentStatusEnum::Confirmed])
            ->where('start_time', '>=', now())
            ->update([
                'appointment_status' => AppointmentStatusEnum::Cancelled,
                'cancel_reason' => 'Klinika müvəqqəti bağlanmışdır',
                'cancelled_at' => now(),
            ]);

        // TODO: Xəstələrə bildiriş göndərmək lazımdır
    }
}
