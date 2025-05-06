<?php

namespace App\Repositories\Module;

use App\Enums\PaymentServiceKeyEnum;
use App\Enums\PaymentServiceOptionTypeEnum;
use App\Enums\PaymentServiceTypeEnum;
use App\Models\PaymentService;
use App\Repositories\BaseRepository;
use App\Services\Filter\PaymentServiceFilter;
use Illuminate\Database\Eloquent\Collection;

class PaymentServiceRepository extends BaseRepository
{
    public function __construct(PaymentService $model)
    {
        parent::__construct($model);
        $this->setFilter(new PaymentServiceFilter(request()));
    }

    public function findListByType($type): Collection
    {
        return $this->model
            ->with('options')
            ->where([
                'type' => $type,
                'is_active' => true
            ])
            ->get();
    }

    public function findListListingByKeyName($name, $result = 'list')
    {
        $data = $this->model
            ->with('options')
            ->where([
                'type' => PaymentServiceTypeEnum::Listing,
                'key_name' => $name,
                'is_active' => true
            ]);
        if ($result === 'list') {
            $data = $data->get();
        } else {
            $data = $data->firstOrFail();
        }

        return $data;
    }

    public function findListCompanyByKeyName($name, $result = 'list')
    {
        $data = $this->model
            ->with('options')
            ->where([
                'type' => PaymentServiceTypeEnum::Company,
                'key_name' => $name,
                'is_active' => true
            ]);
        if ($result === 'list') {
            $data = $data->get();
        } else {
            $data = $data->firstOrFail();
        }

        return $data;
    }

    public function filters(): array
    {
        $types = collect(PaymentServiceTypeEnum::getValues())->map(fn($i) => [
            'id' => $i,
            'name' => PaymentServiceTypeEnum::getDescription($i)
        ]);

        $optionTypes = collect(PaymentServiceOptionTypeEnum::getValues())->map(fn($i) => [
            'id' => $i,
            'name' => PaymentServiceOptionTypeEnum::getDescription($i)
        ]);

        $keyNames = collect(PaymentServiceKeyEnum::getValues())->map(fn($i) => [
            'id' => $i,
            'name' => PaymentServiceKeyEnum::getDescription($i)
        ]);

        return [
            'types' => $types,
            'option_types' => $optionTypes,
            'key_names' => $keyNames,
        ];
    }

    public function findByIdOptions($id)
    {
        $data = $this->findById($id);
        return $data->options()->get();
    }

    public function saveOption(int $id, array $data)
    {
        $service = $this->findById($id);
        $optionId = $data['id'] ?? null;
        return $service->options()->updateOrCreate(
            [
                'id' => $optionId,
                'payment_service_id' => $id
            ],
            [
                'value' => $data['value'] ?? null,
                'amount' => $data['amount'] ?? null,
                'custom_fields' => $data['custom_fields'] ?? null,
            ]
        );
    }
}
