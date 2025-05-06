<?php

namespace App\Repositories\Module;

use App\Enums\CredentialTypeEnum;
use App\Models\BlockedCredential;
use App\Repositories\BaseRepository;
use App\Services\Filter\BlockedCredentialFilter;
use Carbon\Carbon;

class BlockedCredentialRepository extends BaseRepository
{
    public function __construct(BlockedCredential $model)
    {
        parent::__construct($model);
        $this->setFilter(new BlockedCredentialFilter(request()));
        $this->with = ['creator'];
    }

    public function filters(): array
    {
        $types = collect(CredentialTypeEnum::getValues())->map(fn($i) => [
            'id' => $i,
            'name' => CredentialTypeEnum::getDescription($i)
        ]);

        return [
            'types' => $types,
        ];
    }

    /**
     * Aktiv bloku credential tipinə və dəyərinə görə tapır
     */
    public function findActiveByCredential(string $type, string $value): ?BlockedCredential
    {
        return $this->model->query()
            ->where('type', $type)
            ->where('value', $value)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('blocked_until')
                    ->orWhere('blocked_until', '>', Carbon::now());
            })
            ->latest()
            ->first();
    }
}
