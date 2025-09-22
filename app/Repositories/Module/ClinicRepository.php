<?php

namespace App\Repositories\Module;

use App\Models\Clinic;
use App\Repositories\BaseRepository;
use App\Services\Filter\ClinicFilter;
use Illuminate\Database\Eloquent\Collection;

class ClinicRepository extends BaseRepository
{
    public function __construct(Clinic $model)
    {
        parent::__construct($model);
        $this->setFilter(new ClinicFilter(request()));
        $this->with = ['categories'];
    }

    /**
     * E-poçt vasitəsilə klinika tapır
     */
    public function findByEmail(string $email): ?Clinic
    {
        return $this->model->where('email', $email)->first();
    }

    /**
     * Telefon vasitəsilə klinika tapır
     */
    public function findByPhone(string $phone): ?Clinic
    {
        return $this->model->where('phone', $phone)->first();
    }

    /**
     * Şəhərə görə klinikalar
     */
    public function findByCity(int $cityId): Collection
    {
        return $this->model->where('city_id', $cityId)
            ->where('is_active', true)
            ->get();
    }

    /**
     * Təsdiqlənmiş və aktiv klinikalar
     */
    public function getVerifiedClinics(): Collection
    {
        return $this->model->where('is_verified', true)
            ->where('is_active', true)
            ->orderBy('rating', 'desc')
            ->get();
    }

    /**
     * Yaxınlıqdakı klinikalar (koordinatlara görə)
     */
    public function findNearby(float $latitude, float $longitude, int $radius = 10): Collection
    {
        return $this->model->selectRaw('*,
            (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance',
            [$latitude, $longitude, $latitude])
            ->having('distance', '<', $radius)
            ->where('is_active', true)
            ->orderBy('distance')
            ->get();
    }

    /**
     * Populyar klinikalar (reytinqə görə)
     */
    public function getPopularClinics(int $limit = 10): Collection
    {
        return $this->model->where('is_active', true)
            ->where('reviews_count', '>', 0)
            ->orderBy('rating', 'desc')
            ->orderBy('reviews_count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Filters üçün data
     */
    public function filters(): array
    {
        return [
            'cities' => \App\Models\City::active()->get(['id', 'translates'])->map(fn($i) => [
                'id' => $i->id,
                'name' => $i->name,
            ]),
            'regions' => \App\Models\Region::active()->get(['id', 'translates'])->map(fn($i) => [
                'id' => $i->id,
                'name' => $i->name,
            ]),
            'categories' => app(CategoryRepository::class)->fetchCategoryByParent()->map(fn($i) => [
                'id' => $i->id,
                'name' => $i->name,
                'children' => $i->children,
            ]),
        ];
    }

    /**
     * Klinika statistikaları
     */
    public function getStatistics(int $clinicId): array
    {
        $clinic = $this->findById($clinicId);

        return [
            'total_doctors' => $clinic->doctors()->count(),
            'active_doctors' => $clinic->doctors()->where('is_active', true)->count(),
            'total_appointments' => $clinic->appointments()->count(),
            'completed_appointments' => $clinic->appointments()->where('status', 'completed')->count(),
            'pending_appointments' => $clinic->appointments()->where('status', 'pending')->count(),
            'total_reviews' => $clinic->reviews()->count(),
            'average_rating' => $clinic->reviews()->avg('rating') ?? 0,
            'this_month_appointments' => $clinic->appointments()
                ->whereBetween('appointment_date', [now()->startOfMonth(), now()->endOfMonth()])
                ->count(),
        ];
    }

    protected function afterCreate($model): void
    {
        // Klinika yaradıldıqdan sonra default working hours yaradırıq
        $this->createDefaultWorkingHours($model);
    }

    protected function afterUpdate($model): void
    {
        // Cache təmizləmə
        if ($this->useCache) {
            $this->clearCache();
        }
    }

    /**
     * Default iş saatları yaradır
     */
    private function createDefaultWorkingHours(Clinic $clinic): void
    {
        $defaultHours = [
            'Monday' => ['open_time' => '09:00', 'close_time' => '18:00', 'is_closed' => false],
            'Tuesday' => ['open_time' => '09:00', 'close_time' => '18:00', 'is_closed' => false],
            'Wednesday' => ['open_time' => '09:00', 'close_time' => '18:00', 'is_closed' => false],
            'Thursday' => ['open_time' => '09:00', 'close_time' => '18:00', 'is_closed' => false],
            'Friday' => ['open_time' => '09:00', 'close_time' => '18:00', 'is_closed' => false],
            'Saturday' => ['open_time' => '09:00', 'close_time' => '14:00', 'is_closed' => false],
            'Sunday' => ['open_time' => null, 'close_time' => null, 'is_closed' => true],
        ];

        foreach ($defaultHours as $day => $hours) {
            $clinic->workingHours()->create([
                'day_of_week' => $day,
                'open_time' => $hours['open_time'],
                'close_time' => $hours['close_time'],
                'is_closed' => $hours['is_closed'],
            ]);
        }
    }
}
