<?php

namespace App\Repositories\Module;

use App\Models\Region;
use App\Repositories\BaseRepository;
use App\Services\Filter\RegionFilter;

class RegionRepository extends BaseRepository
{
    public function __construct(Region $model)
    {
        parent::__construct($model);
        $this->setFilter(new RegionFilter(request()));
        $this->with = ['country', 'city'];
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
