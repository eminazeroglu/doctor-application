<?php

namespace App\Repositories\Module;

use App\Http\Resources\Admin\CategoryResource;
use App\Models\Service;
use App\Repositories\BaseRepository;
use App\Services\Filter\ServiceFilter;
use Illuminate\Database\Eloquent\Collection;

class ServiceRepository extends BaseRepository
{
    public function __construct(Service $model)
    {
        parent::__construct($model);
        $this->with = ['category'];
        $this->setFilter(new ServiceFilter(request()));
    }

    /**
     * Kateqoriyaya aid xidmətləri tapır
     */
    public function findByCategory(int $categoryId): Collection
    {
        return $this->model->newQuery()
            ->where('category_id', $categoryId)
            ->where('is_active', true)
            ->orderBy('order')
            ->get();
    }

    /**
     * Populyar xidmətləri tapır
     */
    public function findPopular(int $limit = 10): Collection
    {
        return $this->model->newQuery()
            ->where('is_active', true)
            ->where('is_popular', true)
            ->orderBy('order')
            ->limit($limit)
            ->get();
    }

    /**
     * Həkimə aid xidmətləri tapır
     */
    public function findByDoctor(int $doctorId): Collection
    {
        return $this->model->newQuery()
            ->whereHas('doctors', function ($query) use ($doctorId) {
                $query->where('users.id', $doctorId);
            })
            ->where('is_active', true)
            ->orderBy('order')
            ->get();
    }

    /**
     * Filtrlər üçün əlavə məlumatlar
     */
    public function filters(): array
    {
        return [
            'categories' => CategoryResource::collection(app(CategoryRepository::class)->getActiveParentCategories(true))
        ];
    }
}
