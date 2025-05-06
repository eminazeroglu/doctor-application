<?php

namespace App\Repositories\Module;

use App\Enums\AdvertisementDisplayTypeEnum;
use App\Enums\AdvertisementPositionEnum;
use App\Models\Advertisement;
use App\Repositories\BaseRepository;
use App\Services\Filter\AdvertisementFilter;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdvertisementRepository extends BaseRepository
{
    public function __construct(Advertisement $model)
    {
        parent::__construct($model);
        $this->setFilter(new AdvertisementFilter(request()));
    }

    public function create(array $data)
    {
        $cacheKey = $this->getCacheKey('create', $data);

        return $this->remember($cacheKey, function () use ($data) {
            try {
                DB::beginTransaction();
                Schema::disableForeignKeyConstraints();

                $model = $this->model->query()->create($data);

                Schema::enableForeignKeyConstraints();
                DB::commit();

                if ($this->useCache) {
                    $this->clearCache();
                }

                return $model;
            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }
        });
    }

    public function update(int $id, array $data): Model
    {
        try {
            $model = $this->findById($id);

            DB::beginTransaction();
            Schema::disableForeignKeyConstraints();

            $data = $this->uploadImageControl($data);
            $model->update($data);

            $this->applyRelations($model);
            $this->afterUpdate($model);

            Schema::enableForeignKeyConstraints();
            DB::commit();

            if ($this->useCache) {
                $this->clearCache();
            }

            return $model;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function filters(): array
    {
        $positions = collect(AdvertisementPositionEnum::getValues())->map(fn($i) => [
            'id' => $i,
            'name' => AdvertisementPositionEnum::getDescription($i),
        ]);

        $displayTypes = collect(AdvertisementDisplayTypeEnum::getValues())->map(fn($i) => [
            'id' => $i,
            'name' => AdvertisementDisplayTypeEnum::getDescription($i),
        ]);

        return [
            'positions' => $positions,
            'display_types' => $displayTypes,
        ];
    }
}
