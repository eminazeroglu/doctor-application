<?php

namespace App\Services\Module;

use App\Repositories\Module\ServiceRepository;
use App\Services\BaseCrudService;
use Illuminate\Database\Eloquent\Collection;

class ServiceService extends BaseCrudService
{
    public function __construct(ServiceRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Kateqoriyaya aid xidmətləri tapır
     */
    public function findByCategory(int $categoryId): Collection
    {
        return $this->repository->findByCategory($categoryId);
    }

    /**
     * Populyar xidmətləri tapır
     */
    public function findPopular(int $limit = 10): Collection
    {
        return $this->repository->findPopular($limit);
    }

    /**
     * Həkimə aid xidmətləri tapır
     */
    public function findByDoctor(int $doctorId): Collection
    {
        return $this->repository->findByDoctor($doctorId);
    }

    /**
     * Həkimin xidmətlərini sinxronlaşdırır
     */
    public function syncDoctorServices(int $doctorId, array $services): void
    {
        $doctor = \App\Models\User::findOrFail($doctorId);
        $pivotData = [];

        foreach ($services as $serviceData) {
            $pivotData[$serviceData['service_id']] = [
                'custom_price' => $serviceData['custom_price'] ?? null,
                'custom_duration' => $serviceData['custom_duration'] ?? null,
                'custom_fields' => $serviceData['custom_fields'] ?? null,
            ];
        }

        $doctor->medicalServices()->sync($pivotData);
    }

    /**
     * Həkimin xidmətlərini əldə edir
     */
    public function getDoctorServices(int $doctorId): array
    {
        $doctor = \App\Models\User::findOrFail($doctorId);
        $services = $doctor->medicalServices()->with('category')->get();

        return [
            'doctor' => $doctor,
            'services' => $services
        ];
    }
}
