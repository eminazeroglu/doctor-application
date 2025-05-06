<?php

namespace App\Repositories\Module;

use App\Models\Country;
use App\Repositories\BaseRepository;
use App\Services\Filter\CountryFilter;
use Illuminate\Database\Eloquent\Collection;

class CountryRepository extends BaseRepository
{
    public function __construct(Country $model)
    {
        parent::__construct($model);
        $this->setFilter(new CountryFilter(request()));
    }

    public function findActiveList(): Collection
    {
        $cacheKey = $this->getCacheKey('findActiveList');

        return $this->remember($cacheKey, function () {
            $query = $this->model->query();

            if (request()->has('withCity')) {
                $query->with(['cities']);
            }

            if (request()->has('withFull')) {
                $query->with([
                    'cities',
                    'cities.regions',
                    'cities.regions.subways',
                ]);
            }

            if ($this->tableHasColumn('is_active')) {
                $query->where('is_active', true);
            }

            $this->applyRelations($query);
            return $query->get();
        });
    }

    public function fetchByUuidWithCities($uuid)
    {
        return $this->executeWithCache('fetchByUuidWithCities.' . $uuid, function () use ($uuid) {
            $country = $this->baseQuery()
                ->with('cities')
                ->where('uuid', $uuid)
                ->firstOrFail();
            return $country->cities;
        });
    }
}
