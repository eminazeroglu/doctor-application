<?php

namespace App\Services\Filter;

use Illuminate\Database\Eloquent\Builder;

class NotificationLogFilter extends BaseFilter
{
    protected array $filters = [
        'search',
        'channel',
        'notification_type',
        'is_successful',
        'user_id',
        'recipient',
        'template_code',
        'error_category',
        'date_range',
        'sent_date_range'
    ];

    protected string $defaultSortColumn = 'sent_at';
    protected string $defaultSortDirection = 'desc';

    /**
     * Axtarış filtri (alıcı, mövzu, məzmun və xəta mesajında)
     */
    protected function filterSearch(Builder $query, string $value): Builder
    {
        return $query->where(function($q) use ($value) {
            $q->where('recipient', 'like', "%{$value}%")
                ->orWhere('subject', 'like', "%{$value}%")
                ->orWhere('content', 'like', "%{$value}%")
                ->orWhere('error_message', 'like', "%{$value}%");
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
     * Notification növü filtri
     */
    protected function filterNotificationType(Builder $query, $value): Builder
    {
        if (is_array($value)) {
            return $query->whereIn('notification_type', $value);
        }
        return $query->where('notification_type', $value);
    }

    /**
     * Uğurlu/uğursuz filtri
     */
    protected function filterIsSuccessful(Builder $query, $value): Builder
    {
        if ($value === true || $value === '1' || $value === 1) {
            return $query->where('is_successful', true);
        } elseif ($value === false || $value === '0' || $value === 0) {
            return $query->where('is_successful', false);
        }
        return $query;
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
     * Alıcı filtri
     */
    protected function filterRecipient(Builder $query, string $value): Builder
    {
        return $query->where('recipient', 'like', "%{$value}%");
    }

    /**
     * Template kodu filtri
     */
    protected function filterTemplateCode(Builder $query, $value): Builder
    {
        if (is_array($value)) {
            return $query->whereIn('template_code', $value);
        }
        return $query->where('template_code', $value);
    }

    /**
     * Xəta kateqoriyası filtri
     */
    protected function filterErrorCategory(Builder $query, $value): Builder
    {
        $errorPatterns = [
            'network' => ['network', 'timeout', 'connection'],
            'validation' => ['invalid', 'format', 'malformed'],
            'authentication' => ['unauthorized', 'forbidden', 'authentication'],
            'rate_limit' => ['rate limit', 'quota', 'throttle'],
            'server' => ['server error', '500', 'internal error'],
            'recipient' => ['not found', 'blocked', 'unsubscribed']
        ];

        if (isset($errorPatterns[$value])) {
            return $query->where(function($q) use ($errorPatterns, $value) {
                foreach ($errorPatterns[$value] as $pattern) {
                    $q->orWhere('error_message', 'like', "%{$pattern}%");
                }
            });
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
     * Göndərilmə tarix aralığı filtri
     */
    protected function filterSentDateRange(Builder $query, array $value): Builder
    {
        if (isset($value['from'])) {
            $query->whereDate('sent_at', '>=', $value['from']);
        }

        if (isset($value['to'])) {
            $query->whereDate('sent_at', '<=', $value['to']);
        }

        return $query;
    }

    /**
     * Bugünkü log-lar filtri
     */
    protected function filterToday(Builder $query, $value): Builder
    {
        if ($value === true || $value === '1' || $value === 1) {
            return $query->whereDate('sent_at', today());
        }
        return $query;
    }

    /**
     * Bu həftənin log-ları filtri
     */
    protected function filterThisWeek(Builder $query, $value): Builder
    {
        if ($value === true || $value === '1' || $value === 1) {
            return $query->whereBetween('sent_at', [
                now()->startOfWeek(),
                now()->endOfWeek()
            ]);
        }
        return $query;
    }

    /**
     * Bu ayın log-ları filtri
     */
    protected function filterThisMonth(Builder $query, $value): Builder
    {
        if ($value === true || $value === '1' || $value === 1) {
            return $query->whereMonth('sent_at', now()->month)
                ->whereYear('sent_at', now()->year);
        }
        return $query;
    }

    /**
     * Yalnız e-poçt log-ları
     */
    protected function filterEmailOnly(Builder $query, $value): Builder
    {
        if ($value === true || $value === '1' || $value === 1) {
            return $query->where('channel', 'email');
        }
        return $query;
    }

    /**
     * Yalnız SMS log-ları
     */
    protected function filterSmsOnly(Builder $query, $value): Builder
    {
        if ($value === true || $value === '1' || $value === 1) {
            return $query->where('channel', 'sms');
        }
        return $query;
    }

    /**
     * Yalnız push log-ları
     */
    protected function filterPushOnly(Builder $query, $value): Builder
    {
        if ($value === true || $value === '1' || $value === 1) {
            return $query->where('channel', 'push');
        }
        return $query;
    }

    /**
     * Xəta mesajı olan log-lar
     */
    protected function filterHasError(Builder $query, $value): Builder
    {
        if ($value === true || $value === '1' || $value === 1) {
            return $query->where('is_successful', false)
                ->whereNotNull('error_message')
                ->where('error_message', '!=', '');
        } elseif ($value === false || $value === '0' || $value === 0) {
            return $query->where(function($q) {
                $q->where('is_successful', true)
                    ->orWhereNull('error_message')
                    ->orWhere('error_message', '');
            });
        }
        return $query;
    }

    /**
     * Template ilə göndərilən log-lar
     */
    protected function filterWithTemplate(Builder $query, $value): Builder
    {
        if ($value === true || $value === '1' || $value === 1) {
            return $query->whereNotNull('template_code')
                ->where('template_code', '!=', '');
        } elseif ($value === false || $value === '0' || $value === 0) {
            return $query->where(function($q) {
                $q->whereNull('template_code')
                    ->orWhere('template_code', '');
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
     * Müəyyən domain-ə göndərilən e-poçt log-ları
     */
    protected function filterEmailDomain(Builder $query, string $value): Builder
    {
        return $query->where('channel', 'email')
            ->where('recipient', 'like', "%@{$value}%");
    }

    /**
     * Müəyyən telefon prefiksinə göndərilən SMS log-ları
     */
    protected function filterPhonePrefix(Builder $query, string $value): Builder
    {
        return $query->where('channel', 'sms')
            ->where('recipient', 'like', "{$value}%");
    }

    /**
     * Yenidən cəhd edilə bilən uğursuz log-lar
     */
    protected function filterRetryable(Builder $query, $value): Builder
    {
        if ($value === true || $value === '1' || $value === 1) {
            $nonRetryablePatterns = [
                'invalid email',
                'invalid phone',
                'blocked recipient',
                'unsubscribed',
                'malformed'
            ];

            return $query->where('is_successful', false)
                ->where(function($q) use ($nonRetryablePatterns) {
                    foreach ($nonRetryablePatterns as $pattern) {
                        $q->where('error_message', 'not like', "%{$pattern}%");
                    }
                });
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
            'channel',
            'recipient',
            'notification_type',
            'is_successful',
            'sent_at',
            'created_at'
        ];
    }
}
