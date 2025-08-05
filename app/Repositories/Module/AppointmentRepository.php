<?php

namespace App\Repositories\Module;

use App\Enums\AppointmentStatusEnum;
use App\Http\Resources\Admin\ReferenceResource;
use App\Models\Appointment;
use App\Repositories\BaseRepository;
use App\Services\Filter\AppointmentFilter;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class AppointmentRepository extends BaseRepository
{
    public function __construct(Appointment $model)
    {
        parent::__construct($model);
        $this->setFilter(new AppointmentFilter(request()));
        $this->with = [
            'patient.user',
            'doctor.user',
            'doctor.category',
            'clinic',
            'service',
            'payment'
        ];
    }

    /**
     * Randevu filterleri
     */
    public function filters(): array
    {
        return [
            'statuses' => $this->getAppointmentStatuses(),
            'doctors' => ReferenceResource::collection(app(DoctorRepository::class)->findActiveList()),
            'clinics' => ReferenceResource::collection(app(ClinicRepository::class)->findActiveList()),
            'services' => ReferenceResource::collection(app(ServiceRepository::class)->findActiveList()),
            'consultation_types' => $this->getConsultationTypes(),
        ];
    }

    /**
     * Randevu statusları
     */
    public function getAppointmentStatuses(): array
    {
        return collect(AppointmentStatusEnum::getValues())->map(fn($i) => [
            'id' => $i,
            'name' => AppointmentStatusEnum::getDescription($i)
        ])->toArray();
    }

    /**
     * Konsultasiya növləri
     */
    public function getConsultationTypes(): array
    {
        return [
            ['id' => 'in_person', 'name' => 'Üz-üzə'],
            ['id' => 'online', 'name' => 'Onlayn'],
            ['id' => 'home_visit', 'name' => 'Ev ziyarəti'],
        ];
    }

    /**
     * Bu günkü randevular
     */
    public function getTodayAppointments(): Collection
    {
        return $this->model->newQuery()
            ->with($this->with)
            ->whereDate('start_time', today())
            ->orderBy('start_time')
            ->get();
    }

    /**
     * Gələcək randevular
     */
    public function getUpcomingAppointments(int $days = 7): Collection
    {
        return $this->model->newQuery()
            ->with($this->with)
            ->whereBetween('start_time', [now(), now()->addDays($days)])
            ->whereIn('appointment_status', ['pending', 'confirmed', 'rescheduled'])
            ->orderBy('start_time')
            ->get();
    }

    /**
     * Randevu konflikti yoxlaması
     */
    public function checkConflict(int $doctorId, string $startTime, string $endTime, int $excludeId = null): bool
    {
        $query = $this->model->newQuery()
            ->where('doctor_id', $doctorId)
            ->whereIn('appointment_status', ['pending', 'confirmed', 'rescheduled'])
            ->where(function ($q) use ($startTime, $endTime) {
                $q->where(function ($subQ) use ($startTime, $endTime) {
                    $subQ->where('start_time', '<', $endTime)
                        ->where('end_time', '>', $startTime);
                });
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Randevu statusunu yenilə
     */
    public function updateStatus(int $id, string $status, string $reason = null): Model
    {
        $updateData = ['appointment_status' => $status];

        if ($status === 'cancelled' && $reason) {
            $updateData['cancel_reason'] = $reason;
        }

        return $this->update($id, $updateData);
    }

    /**
     * Randevu statistikaları
     */
    public function getStatistics(array $filters = []): array
    {
        $query = $this->model->newQuery();

        // Filterlər tətbiq et
        if (!empty($filters['date_range'])) {
            $query->whereBetween('start_time', [
                $filters['date_range']['from'],
                $filters['date_range']['to']
            ]);
        }

        if (!empty($filters['doctor_id'])) {
            $query->where('doctor_id', $filters['doctor_id']);
        }

        if (!empty($filters['clinic_id'])) {
            $query->where('clinic_id', $filters['clinic_id']);
        }

        return [
            'total' => $query->count(),
            'pending' => (clone $query)->where('appointment_status', 'pending')->count(),
            'confirmed' => (clone $query)->where('appointment_status', 'confirmed')->count(),
            'completed' => (clone $query)->where('appointment_status', 'completed')->count(),
            'cancelled' => (clone $query)->where('appointment_status', 'cancelled')->count(),
            'no_show' => (clone $query)->where('appointment_status', 'no-show')->count(),
            'paid' => (clone $query)->where('is_paid', true)->count(),
            'unpaid' => (clone $query)->where('is_paid', false)->count(),
        ];
    }
}
