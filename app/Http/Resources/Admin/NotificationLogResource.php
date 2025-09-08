<?php

namespace App\Http\Resources\Admin;

use App\Enums\NotificationChannelEnum;
use App\Enums\NotificationTypeEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationLogResource extends JsonResource
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
            'uuid' => $this->uuid,
            'channel' => $this->channel,
            'channel_text' => NotificationChannelEnum::getDescription($this->channel),
            'channel_icon' => $this->getChannelIcon(),
            'recipient' => $this->recipient,
            'masked_recipient' => $this->getMaskedRecipient(),
            'notification_type' => $this->notification_type,
            'notification_type_text' => NotificationTypeEnum::getDescription($this->notification_type),
            'template_code' => $this->template_code,
            'subject' => $this->subject,
            'content' => $this->content,
            'content_preview' => $this->getContentPreview(),
            'is_successful' => $this->is_successful,
            'status' => $this->is_successful ? 'success' : 'failed',
            'status_text' => $this->is_successful ? 'Uğurlu' : 'Uğursuz',
            'status_color' => $this->is_successful ? 'success' : 'error',
            'error_message' => $this->error_message,
            'error_category' => $this->getErrorCategory(),
            'sent_at' => $this->sent_at->toIso8601String(),
            'sent_at_human' => $this->sent_at->diffForHumans(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),

            // İstifadəçi məlumatları (əgər yüklənibsə)
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'surname' => $this->user->surname,
                    'fullname' => $this->user->fullname,
                    'email' => $this->user->email,
                    'photo' => $this->user->photo
                ];
            }),

            // Template məlumatları (əgər yüklənibsə)
            'template' => $this->when(
                $this->template_code && $this->relationLoaded('template'),
                function () {
                    return [
                        'name' => $this->template->name,
                        'code' => $this->template->code,
                        'type' => $this->template->type
                    ];
                }
            ),

            // Mətn analizi
            'content_analysis' => [
                'length' => strlen($this->content),
                'word_count' => str_word_count($this->content),
                'contains_html' => $this->content !== strip_tags($this->content),
                'contains_links' => $this->containsLinks(),
                'estimated_reading_time' => $this->getEstimatedReadingTime()
            ],

            // Delivery məlumatları
            'delivery_info' => [
                'delivery_time' => $this->getDeliveryTime(),
                'retry_count' => $this->getRetryCount(),
                'final_attempt' => $this->isFinalAttempt()
            ],

            // Debug məlumatları (development üçün)
            'debug_info' => $this->when(
                config('app.debug') && !$this->is_successful,
                function () {
                    return [
                        'full_error' => $this->error_message,
                        'sent_timestamp' => $this->sent_at->timestamp,
                        'content_hash' => md5($this->content)
                    ];
                }
            ),

            // Actions
            'can_retry' => !$this->is_successful && $this->canRetry(),
            'can_view_details' => true,
            'can_export' => true
        ];
    }

    /**
     * Kanal üçün ikon qaytarır
     */
    protected function getChannelIcon(): string
    {
        return match($this->channel) {
            'email' => 'mail',
            'sms' => 'message-square',
            'push' => 'bell',
            'in_app' => 'monitor',
            default => 'help-circle'
        };
    }

    /**
     * Alıcı məlumatını masklayır (məxfilik üçün)
     */
    protected function getMaskedRecipient(): string
    {
        if ($this->channel === 'email') {
            // Email: test@example.com -> t***@e***.com
            $parts = explode('@', $this->recipient);
            if (count($parts) === 2) {
                $username = $parts[0];
                $domain = $parts[1];

                $maskedUsername = substr($username, 0, 1) . str_repeat('*', max(0, strlen($username) - 1));
                $domainParts = explode('.', $domain);
                if (count($domainParts) >= 2) {
                    $maskedDomain = substr($domainParts[0], 0, 1) . str_repeat('*', max(0, strlen($domainParts[0]) - 1)) . '.' . end($domainParts);
                } else {
                    $maskedDomain = substr($domain, 0, 1) . str_repeat('*', max(0, strlen($domain) - 1));
                }

                return $maskedUsername . '@' . $maskedDomain;
            }
        } elseif ($this->channel === 'sms') {
            // Phone: +994501234567 -> +994***1234567
            if (strlen($this->recipient) > 6) {
                return substr($this->recipient, 0, 4) . str_repeat('*', 3) . substr($this->recipient, -4);
            }
        }

        // Digər hallarda son 4 rəqəm/hərf göstərilir
        if (strlen($this->recipient) > 8) {
            return str_repeat('*', strlen($this->recipient) - 4) . substr($this->recipient, -4);
        }

        return $this->recipient;
    }

    /**
     * Məzmun önizləməsi
     */
    protected function getContentPreview(int $length = 100): string
    {
        $content = strip_tags($this->content);

        if (strlen($content) <= $length) {
            return $content;
        }

        return substr($content, 0, $length) . '...';
    }

    /**
     * Xəta kateqoriyası
     */
    protected function getErrorCategory(): ?string
    {
        if ($this->is_successful || !$this->error_message) {
            return null;
        }

        $error = strtolower($this->error_message);

        if (strpos($error, 'network') !== false || strpos($error, 'timeout') !== false) {
            return 'network';
        } elseif (strpos($error, 'invalid') !== false || strpos($error, 'format') !== false) {
            return 'validation';
        } elseif (strpos($error, 'unauthorized') !== false || strpos($error, 'forbidden') !== false) {
            return 'authentication';
        } elseif (strpos($error, 'rate limit') !== false) {
            return 'rate_limit';
        } elseif (strpos($error, 'server') !== false) {
            return 'server';
        } else {
            return 'other';
        }
    }

    /**
     * Məzmunda link-lərin olub-olmadığını yoxlayır
     */
    protected function containsLinks(): bool
    {
        return preg_match('/https?:\/\/[^\s]+/', $this->content) === 1;
    }

    /**
     * Təxmini oxuma vaxtı (dəqiqə)
     */
    protected function getEstimatedReadingTime(): int
    {
        $wordCount = str_word_count($this->content);
        return max(1, ceil($wordCount / 200)); // 200 söz/dəqiqə
    }

    /**
     * Çatdırılma vaxtı (əgər məlumat varsa)
     */
    protected function getDeliveryTime(): ?int
    {
        // Bu məlumat əlavə metadata-dan gələ bilər
        return null; // Şimdilik null
    }

    /**
     * Yenidən cəhd sayı
     */
    protected function getRetryCount(): int
    {
        // Bu məlumat əlavə metadata-dan gələ bilər
        return 0; // Şimdilik 0
    }

    /**
     * Son cəhd olub-olmadığını yoxlayır
     */
    protected function isFinalAttempt(): bool
    {
        return !$this->is_successful; // Sadələşdirilmiş
    }

    /**
     * Yenidən cəhd edilə bilər-bilməzliyini yoxlayır
     */
    protected function canRetry(): bool
    {
        // Müəyyən xəta növləri üçün retry uyğun deyil
        if (!$this->error_message) {
            return false;
        }

        $error = strtolower($this->error_message);
        $nonRetryableErrors = [
            'invalid email',
            'invalid phone',
            'blocked recipient',
            'unsubscribed'
        ];

        foreach ($nonRetryableErrors as $nonRetryable) {
            if (str_contains($error, $nonRetryable)) {
                return false;
            }
        }

        return true;
    }
}
