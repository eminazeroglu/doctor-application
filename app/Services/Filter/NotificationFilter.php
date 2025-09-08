<?php

namespace App\Services\Filter;

use Illuminate\Database\Eloquent\Builder;

class NotificationFilter extends BaseFilter
{
    protected array $filters = [
        'search',
        'type',
        'user_id',
        'is_read',
        'is_sent',
        'priority',
        'date_range',
        'send_date_range'
    ];

    protected string $defaultSortColumn = 'created_at';
    protected string $defaultSortDirection = 'desc';

    /**
     * Axtarış filtri (başlıq və məzmunda)
     */
    protected function filterSearch(Builder $query, string $value): Builder
    {
        return $query->where(function($q) use ($value) {
            $q->where('title', 'like', "%{$value}%")
                ->orWhere('content', 'like', "%{$value}%");
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
    protected function filterUserId(Builder $query, $value): Builder
    {
        if (is_array($value)) {
            return $query->whereIn('user_id', $value);
        }
        return $query->where('user_id', $value);
    }

    /**
     * Oxunmuş/oxunmamış filtri
     */
    protected function filterIsRead(Builder $query, $value): Builder
    {
        if ($value === true || $value === '1' || $value === 1) {
            return $query->whereNotNull('read_at');
        } elseif ($value === false || $value === '0' || $value === 0) {
            return $query->whereNull('read_at');
        }
        return $query;
    }

    /**
     * Göndərilmiş/göndərilməmiş filtri
     */
    protected function filterIsSent(Builder $query, $value): Builder
    {
        if ($value === true || $value === '1' || $value === 1) {
            return $query->where('is_sent', true);
        } elseif ($value === false || $value === '0' || $value === 0) {
            return $query->where('is_sent', false);
        }
        return $query;
    }

    /**
     * Prioritet filtri
     */
    protected function filterPriority(Builder $query, $value): Builder
    {
        return $query->whereJsonContains('data->priority', $value);
    }

    /**
     * Yaradılma tarix aralığı filtri
     */
    protected function filterDateRange(Builder $query, array $value): Builder
    {
        if (isset($value['from'])) {
            $query->whereDate('created_at', '>=', $value['from']);
        }

        if (isset($value['to'])) {
            $query->whereDate('created_at', '<=', $value['to']);
        }

        return $query;
    }

    /**
     * Göndərilmə tarix aralığı filtri
     */
    protected function filterSendDateRange(Builder $query, array $value): Builder
    {
        if (isset($value['from'])) {
            $query->where(function($q) use ($value) {
                $q->whereDate('send_at', '>=', $value['from'])
                    ->orWhere(function($subQ) use ($value) {
                        $subQ->whereNull('send_at')
                            ->whereDate('created_at', '>=', $value['from']);
                    });
            });
        }

        if (isset($value['to'])) {
            $query->where(function($q) use ($value) {
                $q->whereDate('send_at', '<=', $value['to'])
                    ->orWhere(function($subQ) use ($value) {
                        $subQ->whereNull('send_at')
                            ->whereDate('created_at', '<=', $value['to']);
                    });
            });
        }

        return $query;
    }

    /**
     * Planlaşdırılmış notification-lar filtri
     */
    protected function filterScheduled(Builder $query, $value): Builder
    {
        if ($value === true || $value === '1' || $value === 1) {
            return $query->where('is_sent', false)
                ->where('send_at', '>', now());
        } elseif ($value === false || $value === '0' || $value === 0) {
            return $query->where(function($q) {
                $q->where('is_sent', true)
                    ->orWhereNull('send_at')
                    ->orWhere('send_at', '<=', now());
            });
        }
        return $query;
    }

    /**
     * Gecikmiş notification-lar filtri
     */
    protected function filterOverdue(Builder $query, $value): Builder
    {
        if ($value === true || $value === '1' || $value === 1) {
            return $query->where('is_sent', false)
                ->where('send_at', '<', now())
                ->whereNotNull('send_at');
        }
        return $query;
    }

    /**
     * Bugünkü notification-lar filtri
     */
    protected function filterToday(Builder $query, $value): Builder
    {
        if ($value === true || $value === '1' || $value === 1) {
            return $query->whereDate('created_at', today());
        }
        return $query;
    }

    /**
     * Bu həftənin notification-ları filtri
     */
    protected function filterThisWeek(Builder $query, $value): Builder
    {
        if ($value === true || $value === '1' || $value === 1) {
            return $query->whereBetween('created_at', [
                now()->startOfWeek(),
                now()->endOfWeek()
            ]);
        }
        return $query;
    }

    /**
     * Sıralama üçün icazə verilən sütunlar
     */
    protected function getSortableColumns(): array
    {
        return [
            'id',
            'title',
            'type',
            'user_id',
            'created_at',
            'updated_at',
            'send_at',
            'read_at'
        ];
    }
}
