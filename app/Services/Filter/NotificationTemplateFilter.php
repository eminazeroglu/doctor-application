<?php

namespace App\Services\Filter;

use Illuminate\Database\Eloquent\Builder;

class NotificationTemplateFilter extends BaseFilter
{
    protected array $filters = [
        'search',
        'channel',
        'type',
        'is_active',
        'has_variables',
        'usage_count',
        'date_range'
    ];

    protected string $defaultSortColumn = 'name';
    protected string $defaultSortDirection = 'asc';

    /**
     * Axtarış filtri (ad, kod və məzmunda)
     */
    protected function filterSearch(Builder $query, string $value): Builder
    {
        return $query->where(function($q) use ($value) {
            $q->where('name', 'like', "%{$value}%")
                ->orWhere('code', 'like', "%{$value}%")
                ->orWhere('content', 'like', "%{$value}%")
                ->orWhere('subject', 'like', "%{$value}%");
        });
    }

    /**
     * Kanal filtri
     */
    protected function filterChannel(Builder $query, $value): Builder
    {
        if (is_array($value)) {
            return $query->whereIn('channel', $value);
        }
        return $query->where('channel', $value);
    }

    /**
     * Növ filtri
     */
    protected function filterType(Builder $query, $value): Builder
    {
        if (is_array($value)) {
            return $query->whereIn('type', $value);
        }
        return $query->where('type', $value);
    }

    /**
     * Aktiv/deaktiv filtri
     */
    protected function filterIsActive(Builder $query, $value): Builder
    {
        if ($value === true || $value === '1' || $value === 1) {
            return $query->where('is_active', true);
        } elseif ($value === false || $value === '0' || $value === 0) {
            return $query->where('is_active', false);
        }
        return $query;
    }

    /**
     * Variable-ları olan/olmayan template-lər filtri
     */
    protected function filterHasVariables(Builder $query, $value): Builder
    {
        if ($value === true || $value === '1' || $value === 1) {
            return $query->whereNotNull('variables')
                ->where('variables', '!=', '[]')
                ->where('variables', '!=', 'null');
        } elseif ($value === false || $value === '0' || $value === 0) {
            return $query->where(function($q) {
                $q->whereNull('variables')
                    ->orWhere('variables', '[]')
                    ->orWhere('variables', 'null');
            });
        }
        return $query;
    }

    /**
     * İstifadə sayı filtri
     */
    protected function filterUsageCount(Builder $query, array $value): Builder
    {
        $query->leftJoin('notification_logs', 'notification_templates.code', '=', 'notification_logs.template_code')
            ->selectRaw('notification_templates.*, COUNT(notification_logs.id) as usage_count')
            ->groupBy('notification_templates.id');

        if (isset($value['min'])) {
            $query->havingRaw('COUNT(notification_logs.id) >= ?', [$value['min']]);
        }

        if (isset($value['max'])) {
            $query->havingRaw('COUNT(notification_logs.id) <= ?', [$value['max']]);
        }

        return $query;
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
     * Yalnız e-poçt template-ləri
     */
    protected function filterEmailOnly(Builder $query, $value): Builder
    {
        if ($value === true || $value === '1' || $value === 1) {
            return $query->where('channel', 'email');
        }
        return $query;
    }

    /**
     * Yalnız SMS template-ləri
     */
    protected function filterSmsOnly(Builder $query, $value): Builder
    {
        if ($value === true || $value === '1' || $value === 1) {
            return $query->where('channel', 'sms');
        }
        return $query;
    }

    /**
     * Yalnız push template-ləri
     */
    protected function filterPushOnly(Builder $query, $value): Builder
    {
        if ($value === true || $value === '1' || $value === 1) {
            return $query->where('channel', 'push');
        }
        return $query;
    }

    /**
     * Mövzusu olan template-lər (əsasən e-poçt üçün)
     */
    protected function filterHasSubject(Builder $query, $value): Builder
    {
        if ($value === true || $value === '1' || $value === 1) {
            return $query->whereNotNull('subject')
                ->where('subject', '!=', '');
        } elseif ($value === false || $value === '0' || $value === 0) {
            return $query->where(function($q) {
                $q->whereNull('subject')
                    ->orWhere('subject', '');
            });
        }
        return $query;
    }

    /**
     * Məzmun uzunluğu filtri
     */
    protected function filterContentLength(Builder $query, array $value): Builder
    {
        if (isset($value['min'])) {
            $query->whereRaw('LENGTH(content) >= ?', [$value['min']]);
        }

        if (isset($value['max'])) {
            $query->whereRaw('LENGTH(content) <= ?', [$value['max']]);
        }

        return $query;
    }

    /**
     * Son istifadə edilən template-lər
     */
    protected function filterRecentlyUsed(Builder $query, $value): Builder
    {
        if ($value === true || $value === '1' || $value === 1) {
            return $query->whereExists(function($q) {
                $q->select(\DB::raw(1))
                    ->from('notification_logs')
                    ->whereColumn('notification_logs.template_code', 'notification_templates.code')
                    ->where('notification_logs.sent_at', '>=', now()->subDays(30));
            });
        } elseif ($value === false || $value === '0' || $value === 0) {
            return $query->whereNotExists(function($q) {
                $q->select(\DB::raw(1))
                    ->from('notification_logs')
                    ->whereColumn('notification_logs.template_code', 'notification_templates.code')
                    ->where('notification_logs.sent_at', '>=', now()->subDays(30));
            });
        }
        return $query;
    }

    /**
     * Çox istifadə olunan template-lər
     */
    protected function filterMostUsed(Builder $query, $value): Builder
    {
        if ($value === true || $value === '1' || $value === 1) {
            return $query->leftJoin('notification_logs', 'notification_templates.code', '=', 'notification_logs.template_code')
                ->selectRaw('notification_templates.*, COUNT(notification_logs.id) as usage_count')
                ->groupBy('notification_templates.id')
                ->havingRaw('COUNT(notification_logs.id) > 0')
                ->orderByDesc('usage_count');
        }
        return $query;
    }

    /**
     * Sistem template-ləri filtri
     */
    protected function filterSystemTemplates(Builder $query, $value): Builder
    {
        $systemCodes = [
            'welcome_email',
            'password_reset_email',
            'account_verified_push',
            'appointment_created_email',
            'appointment_confirmed_sms'
        ];

        if ($value === true || $value === '1' || $value === 1) {
            return $query->whereIn('code', $systemCodes);
        } elseif ($value === false || $value === '0' || $value === 0) {
            return $query->whereNotIn('code', $systemCodes);
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
            'name',
            'code',
            'channel',
            'type',
            'is_active',
            'created_at',
            'updated_at'
        ];
    }
}
