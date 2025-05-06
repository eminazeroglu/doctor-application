<?php

namespace App\Services\Filter;

use Illuminate\Database\Eloquent\Builder;
use App\Enums\NotificationPriorityEnum;
use App\Enums\NotificationTypeEnum;

class NotificationFilter extends BaseFilter
{
    protected array $filters = [
        'search',
        'type',
        'priority',
        'read',
        'date_range',
        'trashed'
    ];

    protected function filterSearch(Builder $query, string $value): Builder
    {
        return $query->where(function ($q) use ($value) {
            // data sütunundakı title və message-ları axtarırıq
            $q->whereRaw("LOWER(JSON_EXTRACT(data, '$.title')) LIKE ?", ['%' . strtolower($value) . '%'])
                ->orWhereRaw("LOWER(JSON_EXTRACT(data, '$.message')) LIKE ?", ['%' . strtolower($value) . '%']);
        });
    }

    // Notification növünə görə filter
    protected function filterType(Builder $query, string $value): Builder
    {
        // Əgər göndərilən tip mövcud deyilsə, heç bir filter tətbiq etmirik
        if (!NotificationTypeEnum::hasValue($value)) {
            return $query;
        }
        return $query->where('type', $value);
    }

    // Prioritetə görə filter
    protected function filterPriority(Builder $query, string $value): Builder
    {
        if (!NotificationPriorityEnum::hasValue($value)) {
            return $query;
        }
        return $query->where('priority', $value);
    }

    // Oxunma statusuna görə filter
    protected function filterRead(Builder $query, $value): Builder
    {
        if ($value === 'read') {
            return $query->whereNotNull('read_at');
        }
        if ($value === 'unread') {
            return $query->whereNull('read_at');
        }
        return $query;
    }
}
