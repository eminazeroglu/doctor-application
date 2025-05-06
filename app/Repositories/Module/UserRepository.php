<?php

namespace App\Repositories\Module;

use App\Enums\UserStatusEnum;
use App\Models\User;
use App\Repositories\BaseRepository;
use App\Repositories\Module\Concerns\User\HasFront;
use App\Repositories\Module\Concerns\User\HasStatistics;
use App\Services\Filter\UserFilter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class UserRepository extends BaseRepository
{
    protected bool $useCache = true;

    use HasStatistics,
        HasFront;

    public function __construct(User $model)
    {
        parent::__construct($model);
        $this->setFilter(new UserFilter(request()));
        $this->initializeUser();
    }

    public function findByEmail($email): ?Model
    {
        return $this->findOneWhere(['email' => $email]);
    }

    /**
     * Bütün qeydləri almaq üçün metod
     *
     * @return Collection
     */
    public function findAll(): Collection
    {
        $cacheKey = $this->getCacheKey('findAll');

        return $this->remember($cacheKey, function () {
            $query = $this->model->query()
                ->where('is_system', false);

            if (!empty($this->with)) {
                $query->with($this->with);
            }

            if (!empty($this->withCount)) {
                $query->withCount($this->withCount);
            }

            return $query->get();
        });
    }

    /**
     * Aktiv məlumatları almaq üçün metod
     *
     * @return Collection
     */
    public function findActiveList(): Collection
    {
        $cacheKey = $this->getCacheKey('findActiveList');

        return $this->remember($cacheKey, function () {
            $query = $this->model->query()
                ->where('status', UserStatusEnum::Active)
                ->where('is_system', false);

            if (!empty($this->with)) {
                $query->with($this->with);
            }

            return $query->get();
        });
    }

    public function paginateAndFilter(): LengthAwarePaginator|Collection
    {
        $cacheKey = $this->getCacheKey('paginateAndFilter', request()->all());

        return $this->remember($cacheKey, function () {
            $query = $this->model->newQuery()
                ->where('is_system', false);

            // Relations yükləyirik
            if (!empty($this->with)) {
                $query->with($this->with);
            }

            if (!empty($this->withCount)) {
                $query->withCount($this->withCount);
            }

            // Normal pagination və filter
            if ($this->filter) {
                $query = $this->filter->apply($query);
            }

            return $this->paginate($query);
        });
    }

    public function statusChange(int $id, array $request): Model
    {

        $model = $this->findById($id);
        $model->status = $request['status'];
        $model->save();

        if (!empty($this->with)) {
            $model->load($this->with);
        }

        if (!empty($this->withCount)) {
            $model->loadCount($this->withCount);
        }

        if ($this->useCache) {
            $this->clearCache();
        }

        return $model;
    }

    /**
     * İstifadəçiləri axtarır
     *
     * @param string $search Axtarış mətni
     * @param int $limit Qaytarılacaq maksimum nəticə sayı
     * @return Collection İstifadəçi kolleksiyası
     */
    public function searchUsers(string $search, int $limit = 10): Collection
    {
        return $this->model->newQuery()
            ->where('is_system', false)
            ->where('status', \App\Enums\UserStatusEnum::Active)
            ->where(function($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('surname', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhereRaw("CONCAT(name, ' ', surname) LIKE ?", ["%{$search}%"]);
            })
            ->orderBy('name')
            ->limit($limit)
            ->get();
    }

}
