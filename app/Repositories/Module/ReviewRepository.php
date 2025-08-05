<?php

namespace App\Repositories\Module;

use App\Models\Review;
use App\Repositories\BaseRepository;
use App\Services\Filter\ReviewFilter;
use Illuminate\Database\Eloquent\Collection;

class ReviewRepository extends BaseRepository
{
    public function __construct(Review $model)
    {
        parent::__construct($model);
        $this->setFilter(new ReviewFilter(request()));
        $this->with = [
            'patient',
            'doctor',
            'clinic',
            'appointment',
            'responses'
        ];
        $this->withCount = ['helpful', 'reports'];
    }

    /**
     * Həkim üzrə rəyləri əldə edir
     */
    public function findByDoctor(int $doctorId): Collection
    {
        $cacheKey = $this->getCacheKey('findByDoctor', ['doctor_id' => $doctorId]);

        return $this->remember($cacheKey, function () use ($doctorId) {
            return $this->model->newQuery()
                ->with($this->with)
                ->withCount($this->withCount)
                ->forDoctor($doctorId)
                ->active()
                ->moderated()
                ->orderBy('created_at', 'desc')
                ->get();
        });
    }

    /**
     * Klinika üzrə rəyləri əldə edir
     */
    public function findByClinic(int $clinicId): Collection
    {
        $cacheKey = $this->getCacheKey('findByClinic', ['clinic_id' => $clinicId]);

        return $this->remember($cacheKey, function () use ($clinicId) {
            return $this->model->newQuery()
                ->with($this->with)
                ->withCount($this->withCount)
                ->forClinic($clinicId)
                ->active()
                ->moderated()
                ->orderBy('created_at', 'desc')
                ->get();
        });
    }

    /**
     * Moderasiya gözləyən rəyləri əldə edir
     */
    public function findAwaitingModeration(): Collection
    {
        $cacheKey = $this->getCacheKey('findAwaitingModeration');

        return $this->remember($cacheKey, function () {
            return $this->model->newQuery()
                ->with($this->with)
                ->withCount($this->withCount)
                ->awaitingModeration()
                ->active()
                ->orderBy('created_at', 'asc')
                ->get();
        });
    }

    /**
     * Ən faydalı rəyləri əldə edir
     */
    public function findMostHelpful(int $limit = 10): Collection
    {
        $cacheKey = $this->getCacheKey('findMostHelpful', ['limit' => $limit]);

        return $this->remember($cacheKey, function () use ($limit) {
            return $this->model->newQuery()
                ->with($this->with)
                ->withCount(['helpful' => function($q) {
                    $q->where('is_helpful', true);
                }])
                ->active()
                ->moderated()
                ->orderBy('helpful_count', 'desc')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Şikayət edilmiş rəyləri əldə edir
     */
    public function findReported(): Collection
    {
        $cacheKey = $this->getCacheKey('findReported');

        return $this->remember($cacheKey, function () {
            return $this->model->newQuery()
                ->with($this->with)
                ->withCount($this->withCount)
                ->reported()
                ->orderBy('created_at', 'desc')
                ->get();
        });
    }

    /**
     * Rəy statistikalarını əldə edir
     */
    public function getStatistics(): array
    {
        $cacheKey = $this->getCacheKey('getStatistics');

        return $this->remember($cacheKey, function () {
            $total = $this->model->count();
            $active = $this->model->active()->count();
            $awaiting = $this->model->awaitingModeration()->count();
            $reported = $this->model->reported()->count();
            $averageRating = $this->model->active()->avg('rating');

            return [
                'total' => $total,
                'active' => $active,
                'awaiting_moderation' => $awaiting,
                'reported' => $reported,
                'average_rating' => round($averageRating, 1),
                'by_rating' => [
                    5 => $this->model->active()->withRating(5)->count(),
                    4 => $this->model->active()->withRating(4)->count(),
                    3 => $this->model->active()->withRating(3)->count(),
                    2 => $this->model->active()->withRating(2)->count(),
                    1 => $this->model->active()->withRating(1)->count(),
                ]
            ];
        });
    }

    /**
     * Rəyi təsdiqlə
     */
    public function verify(int $id): bool
    {
        $review = $this->findById($id);
        $result = $review->verify();

        if ($result && $this->useCache) {
            $this->clearCache();
        }

        return $result;
    }

    /**
     * Rəyi moderasiya et
     */
    public function moderate(int $id): bool
    {
        $review = $this->findById($id);
        $result = $review->moderate();

        if ($result && $this->useCache) {
            $this->clearCache();
        }

        return $result;
    }

    /**
     * Toplu moderasiya
     */
    public function bulkModerate(array $ids): bool
    {
        $result = $this->model->whereIn('id', $ids)->update([
            'is_moderated' => true
        ]);

        if ($result && $this->useCache) {
            $this->clearCache();
        }

        return (bool)$result;
    }

    /**
     * Toplu təsdiq
     */
    public function bulkVerify(array $ids): bool
    {
        $result = $this->model->whereIn('id', $ids)->update([
            'is_verified' => true
        ]);

        if ($result && $this->useCache) {
            $this->clearCache();
        }

        return (bool)$result;
    }

    /**
     * Filter seçimləri
     */
    public function filters(): array
    {
        return [
            'statuses' => [
                ['id' => 'active', 'name' => 'Aktiv'],
                ['id' => 'inactive', 'name' => 'Deaktiv'],
                ['id' => 'awaiting_moderation', 'name' => 'Moderasiya gözləyir'],
                ['id' => 'reported', 'name' => 'Şikayət edilmiş'],
            ],
            'ratings' => [
                ['id' => 5, 'name' => '5 ulduz'],
                ['id' => 4, 'name' => '4 ulduz'],
                ['id' => 3, 'name' => '3 ulduz'],
                ['id' => 2, 'name' => '2 ulduz'],
                ['id' => 1, 'name' => '1 ulduz'],
            ],
            'types' => [
                ['id' => 'doctor', 'name' => 'Həkim rəyi'],
                ['id' => 'clinic', 'name' => 'Klinika rəyi'],
            ]
        ];
    }

    protected function afterStatusChange($model): void
    {
        // Status dəyişiklikdən sonra cache təmizlə
        if ($this->useCache) {
            $this->clearCache();
        }
    }
}
