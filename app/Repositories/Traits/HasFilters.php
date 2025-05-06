<?php

namespace App\Repositories\Traits;

use Illuminate\Database\Eloquent\Builder;

trait HasFilters
{
    protected function applyBaseFilters(Builder $query): Builder
    {
        return $query
            // Axtarış filtri - searchableFields mövcudluğu yoxlanılır
            ->when(
                property_exists($this, 'searchableFields') &&
                !empty($this->searchableFields) &&
                request('search'),
                function ($q) {
                    $searchTerm = request('search');
                    return $q->where(function ($query) use ($searchTerm) {
                        foreach ($this->searchableFields as $field) {
                            $query->orWhere($field, 'like', "%{$searchTerm}%");
                        }
                    });
                }
            )
            // Status filtri
            ->when(request('status'), function ($q, $status) {
                return $q->where('status', $status);
            })
            // Aktivlik filtri
            ->when(request()->has('is_active'), function ($q) {
                return $q->where('is_active', request('is_active'));
            })
            // Tarix aralığı filtri
            ->when(request('date_range'), function ($q) {
                $dateRange = request('date_range');
                return $q->when(isset($dateRange['from']), function ($query) use ($dateRange) {
                    return $query->whereDate('created_at', '>=', $dateRange['from']);
                })->when(isset($dateRange['to']), function ($query) use ($dateRange) {
                    return $query->whereDate('created_at', '<=', $dateRange['to']);
                });
            })
            // Silinmiş məlumatların filtri
            ->when(request('trashed'), function ($q, $trashedStatus) {
                return match($trashedStatus) {
                    'with' => $q->withTrashed(),
                    'only' => $q->onlyTrashed(),
                    default => $q
                };
            });
    }
}
