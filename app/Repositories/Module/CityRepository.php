<?php

namespace App\Repositories\Module;

use App\Models\City;
use App\Repositories\BaseRepository;
use App\Services\Filter\CityFilter;

class CityRepository extends BaseRepository
{
    public function __construct(City $model)
    {
        parent::__construct($model);
        $this->setFilter(new CityFilter(request()));
        $this->with = ['country'];
    }

    public function filters(): array
    {
        return [
            'countries' => app(CountryRepository::class)->findActiveList(),
        ];
    }

    public function fetchByUuidWithRegions($uuid)
    {
        return $this->executeWithCache('fetchByUuidWithRegions.' . $uuid, function () use ($uuid) {
            $country = $this->baseQuery()
                ->with('regions')
                ->where('uuid', $uuid)
                ->firstOrFail();
            return $country->regions;
        });
    }

    public function fetchByUuidWithSubways($uuid)
    {
        return $this->executeWithCache('fetchByUuidWithSubways.' . $uuid, function () use ($uuid) {
            $country = $this->baseQuery()
                ->with('subways')
                ->where('uuid', $uuid)
                ->firstOrFail();
            return $country->subways;
        });
    }
}
