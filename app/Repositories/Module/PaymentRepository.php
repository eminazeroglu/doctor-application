<?php

namespace App\Repositories\Module;

use App\Enums\PaymentMethodEnum;
use App\Enums\PaymentStatusEnum;
use App\Helpers\Helper;
use App\Models\Payment;
use App\Repositories\BaseRepository;
use App\Services\Filter\PaymentFilter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class PaymentRepository extends BaseRepository
{
    public function __construct(Payment $model)
    {
        parent::__construct($model);
        $this->setFilter(new PaymentFilter(request()));
        $this->with = ['user', 'paymentable'];
    }

    public function filters()
    {
        return [
            'statuses' => Helper::enumToFilter(PaymentStatusEnum::class),
            'payment_methods' => Helper::enumToFilter(PaymentMethodEnum::class),
        ];
    }

    public function paginateAndFilter(): LengthAwarePaginator|Collection
    {
        $cacheKey = $this->getCacheKey('paginateAndFilter', request()->all());

        return $this->remember($cacheKey, function () {
            $query = $this->model->newQuery();
            $this->applyRelations($query);

            if ($this->filter) {
                $query = $this->filter->apply($query);
            }

            return $this->paginate($query);
        });
    }
}
