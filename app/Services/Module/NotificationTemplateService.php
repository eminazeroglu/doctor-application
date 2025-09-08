<?php

namespace App\Services\Module;

use App\Repositories\Module\NotificationTemplateRepository;
use App\Services\BaseCrudService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class NotificationTemplateService extends BaseCrudService
{
    public function __construct(NotificationTemplateRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Yeni template yaratmaq
     */
    public function create(array $data): Model
    {
        // Variables array-ni JSON-a çevirmək
        if (isset($data['variables']) && is_array($data['variables'])) {
            $data['variables'] = json_encode($data['variables']);
        }

        return $this->repository->create($data);
    }

    /**
     * Template yeniləmək
     */
    public function update(int $id, array $data): Model
    {
        // Variables array-ni JSON-a çevirmək
        if (isset($data['variables']) && is_array($data['variables'])) {
            $data['variables'] = json_encode($data['variables']);
        }

        return $this->repository->update($id, $data);
    }

    /**
     * Müəyyən kanal və növə görə aktiv template tapmaq
     */
    public function findActiveTemplate(string $channel, string $type): ?Model
    {
        return $this->repository->findOneWhere([
            'channel' => $channel,
            'type' => $type,
            'is_active' => true
        ]);
    }

    /**
     * Kanal üzrə template-ləri əldə etmək
     */
    public function getByChannel(string $channel): Collection
    {
        return $this->repository->findWhere([
            'channel' => $channel,
            'is_active' => true
        ]);
    }

    /**
     * Növ üzrə template-ləri əldə etmək
     */
    public function getByType(string $type): Collection
    {
        return $this->repository->findWhere([
            'type' => $type,
            'is_active' => true
        ]);
    }

    /**
     * Template kod-u ilə axtarmaq
     */
    public function findByCode(string $code): ?Model
    {
        return $this->repository->findOneWhere(['code' => $code]);
    }

    /**
     * Template məzmununu dəyişənlərlə parse etmək
     */
    public function parseTemplate(int $templateId, array $variables = []): array
    {
        $template = $this->repository->findById($templateId);

        return [
            'subject' => $template->parseSubject($variables),
            'content' => $template->parseContent($variables),
            'original_subject' => $template->subject,
            'original_content' => $template->content,
            'variables' => $template->variables
        ];
    }

    /**
     * Template-lərin istifadə statistikasını əldə etmək
     */
    public function getUsageStatistics(): array
    {
        // Bu statistika notification_logs cədvəlindən alınacaq
        $stats = \DB::table('notification_logs')
            ->join('notification_templates', 'notification_logs.template_code', '=', 'notification_templates.code')
            ->select('notification_templates.id', 'notification_templates.name', 'notification_templates.code')
            ->selectRaw('COUNT(notification_logs.id) as usage_count')
            ->selectRaw('SUM(CASE WHEN notification_logs.is_successful = 1 THEN 1 ELSE 0 END) as successful_count')
            ->selectRaw('SUM(CASE WHEN notification_logs.is_successful = 0 THEN 1 ELSE 0 END) as failed_count')
            ->groupBy('notification_templates.id', 'notification_templates.name', 'notification_templates.code')
            ->orderByDesc('usage_count')
            ->get();

        return $stats->map(function ($stat) {
            return [
                'template_id' => $stat->id,
                'template_name' => $stat->name,
                'template_code' => $stat->code,
                'total_usage' => $stat->usage_count,
                'successful' => $stat->successful_count,
                'failed' => $stat->failed_count,
                'success_rate' => $stat->usage_count > 0
                    ? round(($stat->successful_count / $stat->usage_count) * 100, 2)
                    : 0
            ];
        })->toArray();
    }

    /**
     * Template duplicate etmək
     */
    public function duplicate(int $templateId, array $overrides = []): Model
    {
        $template = $this->repository->findById($templateId);

        $newData = $template->toArray();
        unset($newData['id'], $newData['created_at'], $newData['updated_at']);

        // Yeni kod yaratmaq
        $newData['code'] = $newData['code'] . '_copy_' . time();
        $newData['name'] = $newData['name'] . ' (Kopya)';

        // Override məlumatları tətbiq etmək
        $newData = array_merge($newData, $overrides);

        return $this->create($newData);
    }

    /**
     * Filter məlumatları
     */
    public function filters(): array
    {
        return [
            'channels' => \DB::table('notification_templates')
                ->select('channel')
                ->distinct()
                ->pluck('channel'),
            'types' => \DB::table('notification_templates')
                ->select('type')
                ->distinct()
                ->pluck('type'),
            'total_count' => \DB::table('notification_templates')->count(),
            'active_count' => \DB::table('notification_templates')->where('is_active', true)->count(),
            'inactive_count' => \DB::table('notification_templates')->where('is_active', false)->count()
        ];
    }

    /**
     * Template backup etmək
     */
    public function backup(): string
    {
        $templates = $this->repository->findAll();
        $backupData = [
            'version' => '1.0',
            'created_at' => now()->toIso8601String(),
            'templates' => $templates->toArray()
        ];

        $fileName = 'notification_templates_backup_' . now()->format('Y_m_d_H_i_s') . '.json';
        $filePath = storage_path('app/backups/' . $fileName);

        // Backup qovluğunu yaratmaq
        if (!file_exists(dirname($filePath))) {
            mkdir(dirname($filePath), 0755, true);
        }

        file_put_contents($filePath, json_encode($backupData, JSON_PRETTY_PRINT));

        return $fileName;
    }

    /**
     * Template restore etmək
     */
    public function restore(string $backupFile): array
    {
        $filePath = storage_path('app/backups/' . $backupFile);

        if (!file_exists($filePath)) {
            throw new \Exception('Backup faylı tapılmadı');
        }

        $backupData = json_decode(file_get_contents($filePath), true);

        if (!$backupData || !isset($backupData['templates'])) {
            throw new \Exception('Yalnış backup faylı formatı');
        }

        $restored = 0;
        $skipped = 0;

        foreach ($backupData['templates'] as $templateData) {
            unset($templateData['id'], $templateData['created_at'], $templateData['updated_at']);

            // Kod konflikti yoxlaması
            if ($this->repository->findOneWhere(['code' => $templateData['code']])) {
                $templateData['code'] = $templateData['code'] . '_restored_' . time();
            }

            try {
                $this->create($templateData);
                $restored++;
            } catch (\Exception $e) {
                $skipped++;
            }
        }

        return [
            'restored' => $restored,
            'skipped' => $skipped,
            'total' => count($backupData['templates'])
        ];
    }
}
