<?php

namespace App\Repositories\Module;

use App\Models\Translate;
use App\Repositories\BaseRepository;
use App\Services\Filter\TranslationFilter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Schema;

class TranslationRepository extends BaseRepository
{
    public function __construct(Translate $model)
    {
        parent::__construct($model);
        $this->setFilter(new TranslationFilter(request()));
    }

    public function paginateAndFilter(): LengthAwarePaginator|Collection
    {
        $cacheKey = $this->getCacheKey('paginateAndFilter', request()->all());

        return $this->remember($cacheKey, function () {
            $query = $this->model
                ->newQuery()
                ->groupBy('key');

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

}
