<?php

namespace App\Services\Filter;

class PaymentFilter extends BaseFilter
{
    protected array $filters = [
        'status',
        'user',
        'payment_method',
    ];

    protected function filterUser($query, $value)
    {
        return $query->whereHas('user', function ($query) use ($value) {
            $query->fullName($value);
        });
    }

    protected function filterPaymentMethod($query, $value)
    {
        return $query->where('payment_method', $value);
    }
}
