<?php

namespace App\Repositories\Module;

use App\Models\NotificationTemplate;
use App\Repositories\BaseRepository;
use App\Services\Filter\NotificationTemplateFilter;

class NotificationTemplateRepository extends BaseRepository
{
    public function __construct(NotificationTemplate $model)
    {
        parent::__construct($model);
        $this->setFilter(new NotificationTemplateFilter(request()));
    }

    /**
     * Aktiv template-ləri əldə etmək
     */
    public function findActiveTemplates(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->model->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * Kanal və növə görə aktiv template tapmaq
     */
    public function findByChannelAndType(string $channel, string $type): ?\Illuminate\Database\Eloquent\Model
    {
        return $this->model->where([
            'channel' => $channel,
            'type' => $type,
            'is_active' => true
        ])->first();
    }

    /**
     * Template kodu ilə axtarmaq
     */
    public function findByCode(string $code): ?\Illuminate\Database\Eloquent\Model
    {
        return $this->model->where('code', $code)->first();
    }

    /**
     * Çox istifadə olunan template-ləri əldə etmək
     */
    public function getMostUsedTemplates(int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return $this->model->leftJoin('notification_logs', 'notification_templates.code', '=', 'notification_logs.template_code')
            ->select('notification_templates.*')
            ->selectRaw('COUNT(notification_logs.id) as usage_count')
            ->groupBy('notification_templates.id')
            ->orderByDesc('usage_count')
            ->limit($limit)
            ->get();
    }

    /**
     * Filter məlumatları
     */
    public function filters(): array
    {
        return [
            'channels' => $this->model->select('channel')
                ->distinct()
                ->orderBy('channel')
                ->pluck('channel')
                ->toArray(),
            'types' => $this->model->select('type')
                ->distinct()
                ->orderBy('type')
                ->pluck('type')
                ->toArray()
        ];
    }
}
