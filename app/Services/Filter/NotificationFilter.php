<?php

namespace App\Services\Filter;

use Illuminate\Database\Eloquent\Builder;

class NotificationFilter extends BaseFilter
{
    /**
     * Mövcud filterlər
     */
    protected array $filters = [
        'search',
        'type',
        'user_id',
        'is_read',
        'is_sent',
        'send_date_from',
        'send_date_to',
        'created_date_from',
        'created_date_to',
        'priority'
    ];

    /**
     * Default sort sütunu
     */
    protected string $defaultSortColumn = 'created_at';

    /**
     * Default sort istiqaməti
     */
    protected string $defaultSortDirection = 'desc';

    /**
     * Axtarış filtri
     */
    protected function filterSearch(Builder $query, string $value): Builder
    {
        return $query->where(function($q) use ($value) {
            $q->where('title', 'like', "%{$value}%")
                ->orWhere('content', 'like', "%{$value}%")
                ->orWhereHas('user', function($userQuery) use ($value) {
                    $userQuery->where('name', 'like', "%{$value}%")
                        ->orWhere('surname', 'like', "%{$value}%")
                        ->orWhere('email', 'like', "%{$value}%");
                });
        });
    }

    /**
     * Notification növü filtri
     */
    protected function filterType(Builder $query, string $value): Builder
    {
        return $query->where('type', $value);
    }

    /**
     * İstifadəçi filtri
     */
    protected function filterUserId(Builder $query, int $value): Builder
    {
        return $query->where('user_id', $value);
    }

    /**
     * Oxunma statusu filtri
     */
    protected function filterIsRead(Builder $query, $value): Builder
    {
        if ($value === 'true' || $value === true || $value === 1) {
            return $query->whereNotNull('read_at');
        } elseif ($value === 'false' || $value === false || $value === 0) {
            return $query->whereNull('read_at');
        }

        return $query;
    }

    /**
     * Göndərmə statusu filtri
     */
    protected function filterIsSent(Builder $query, $value): Builder
    {
        if ($value === 'true' || $value === true || $value === 1) {
            return $query->where('is_sent', true);
        } elseif ($value === 'false' || $value === false || $value === 0) {
            return $query->where('is_sent', false);
        }

        return $query;
    }

    /**
     * Göndərmə tarixi başlanğıc filtri
     */
    protected function filterSendDateFrom(Builder $query, string $value): Builder
    {
        return $query->whereDate('send_at', '>=', $value);
    }

    /**
     * Göndərmə tarixi son filtri
     */
    protected function filterSendDateTo(Builder $query, string $value): Builder
    {
        return $query->whereDate('send_at', '<=', $value);
    }

    /**
     * Yaradılma tarixi başlanğıc filtri
     */
    protected function filterCreatedDateFrom(Builder $query, string $value): Builder
    {
        return $query->whereDate('created_at', '>=', $value);
    }

    /**
     * Yaradılma tarixi son filtri
     */
    protected function filterCreatedDateTo(Builder $query, string $value): Builder
    {
        return $query->whereDate('created_at', '<=', $value);
    }

    /**
     * Prioritet filtri
     */
    protected function filterPriority(Builder $query, string $value): Builder
    {
        return $query->whereJsonContains('data->priority', $value);
    }

    /**
     * Axtarış ediləcək sahələr
     */
    protected function getSearchableFields(): array
    {
        return ['title', 'content'];
    }
}
