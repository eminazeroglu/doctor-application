<?php

namespace App\Http\Resources\Admin;

use App\Enums\NotificationChannelEnum;
use App\Enums\NotificationTemplateTypeEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationTemplateResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'channel' => $this->channel,
            'channel_text' => NotificationChannelEnum::getDescription($this->channel),
            'type' => $this->type,
            'type_text' => NotificationTemplateTypeEnum::getDescription($this->type),
            'subject' => $this->subject,
            'content' => $this->content,
            'variables' => $this->variables,
            'is_active' => $this->is_active,
            'status_text' => $this->is_active ? 'Aktiv' : 'Deaktiv',
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            'created_at_human' => $this->created_at->diffForHumans(),

            // Template məzmunu hakkında məlumat
            'content_length' => strlen($this->content),
            'has_subject' => !empty($this->subject),
            'variables_count' => is_array($this->variables) ? count($this->variables) : 0,

            // Template variable-ları (əgər varsa)
            'parsed_variables' => $this->when(
                !empty($this->variables),
                function () {
                    if (is_string($this->variables)) {
                        return json_decode($this->variables, true) ?? [];
                    }
                    return $this->variables ?? [];
                }
            ),

            // Usage statistikası (əgər yüklənibsə)
            'usage_statistics' => $this->when(
                isset($this->usage_count),
                function () {
                    return [
                        'total_usage' => $this->usage_count ?? 0,
                        'successful_usage' => $this->successful_count ?? 0,
                        'failed_usage' => $this->failed_count ?? 0,
                        'success_rate' => $this->usage_count > 0
                            ? round(($this->successful_count / $this->usage_count) * 100, 2)
                            : 0
                    ];
                }
            ),

            // Template validation məlumatları
            'validation' => [
                'has_required_variables' => $this->hasRequiredVariables(),
                'content_warnings' => $this->getContentWarnings(),
                'channel_compatibility' => $this->getChannelCompatibility()
            ],

            // Template preview məlumatları
            'preview_data' => [
                'estimated_length' => $this->getEstimatedLength(),
                'contains_html' => $this->containsHtml(),
                'contains_links' => $this->containsLinks()
            ],

            // Actions
            'can_edit' => true,
            'can_delete' => !$this->isSystemTemplate(),
            'can_duplicate' => true,
            'can_test' => $this->is_active,
        ];
    }

    /**
     * Template-də lazımi variable-ların olub-olmadığını yoxlayır
     */
    protected function hasRequiredVariables(): bool
    {
        // Əsas variable-lar tip əsasında yoxlanır
        $requiredByType = [
            'appointment' => ['user_name', 'doctor_name', 'appointment_date'],
            'review' => ['user_name', 'rating'],
            'message' => ['user_name', 'sender_name'],
            'system' => ['user_name'],
        ];

        $required = $requiredByType[$this->type] ?? [];
        $available = $this->variables ?? [];

        if (is_string($available)) {
            $available = json_decode($available, true) ?? [];
        }

        return empty(array_diff($required, $available));
    }

    /**
     * Template məzmunu hakkında xəbərdarlıqlar
     */
    protected function getContentWarnings(): array
    {
        $warnings = [];

        // Çox uzun məzmun
        if (strlen($this->content) > 1000) {
            $warnings[] = 'Məzmun çox uzundur, SMS üçün uyğun deyil';
        }

        // Boş subject (email üçün)
        if ($this->channel === 'email' && empty($this->subject)) {
            $warnings[] = 'E-poçt üçün mövzu boş ola bilməz';
        }

        // HTML tag-ları (SMS üçün)
        if ($this->channel === 'sms' && $this->containsHtml()) {
            $warnings[] = 'SMS-də HTML tag-ları işləmir';
        }

        return $warnings;
    }

    /**
     * Kanal uyğunluğu yoxlaması
     */
    protected function getChannelCompatibility(): array
    {
        return [
            'email' => [
                'compatible' => true,
                'notes' => empty($this->subject) ? 'Mövzu əlavə edilməlidir' : null
            ],
            'sms' => [
                'compatible' => strlen($this->content) <= 160,
                'notes' => strlen($this->content) > 160 ? 'Məzmun 160 simvoldan çoxdur' : null
            ],
            'push' => [
                'compatible' => strlen($this->content) <= 200,
                'notes' => strlen($this->content) > 200 ? 'Push notification üçün çox uzundur' : null
            ],
            'in_app' => [
                'compatible' => true,
                'notes' => null
            ]
        ];
    }

    /**
     * Təxmini uzunluq hesablaması
     */
    protected function getEstimatedLength(): int
    {
        // Variable-ları orta uzunluq ilə əvəzləyirik
        $content = $this->content;
        $variables = is_string($this->variables)
            ? json_decode($this->variables, true) ?? []
            : $this->variables ?? [];

        foreach ($variables as $variable) {
            $content = str_replace('{' . $variable . '}', str_repeat('X', 10), $content);
        }

        return strlen($content);
    }

    /**
     * HTML tag-larının mövcudluğunu yoxlayır
     */
    protected function containsHtml(): bool
    {
        return $this->content !== strip_tags($this->content);
    }

    /**
     * Link-lərin mövcudluğunu yoxlayır
     */
    protected function containsLinks(): bool
    {
        return preg_match('/https?:\/\/[^\s]+/', $this->content) ||
            str_contains($this->content, '{action_url}');
    }

    /**
     * Sistem template-i olub-olmadığını yoxlayır
     */
    protected function isSystemTemplate(): bool
    {
        // Sistem template-ləri əl ilə silinə bilməz
        $systemCodes = [
            'welcome_email',
            'password_reset_email',
            'account_verified_push'
        ];

        return in_array($this->code, $systemCodes);
    }
}
