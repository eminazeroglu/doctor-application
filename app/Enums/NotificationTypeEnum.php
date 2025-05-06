<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class NotificationTypeEnum extends Enum
{
    const WELCOME = 'welcome';
    const PASSWORD_RESET = 'password_reset';
    const LOGIN_ALERT = 'login_alert';
    const PROFILE_UPDATE = 'profile_update';
    const NEW_MESSAGE = 'new_message';
    const SYSTEM_ALERT = 'system_alert';
    const REFERRAL_BONUS_ADDED = 'referral_bonus_added';
    const REFERRAL_COMPLETED = 'referral_completed';

    // Elanla bağlı notification tipləri
    const LISTING_CREATED = 'listing_created';
    const LISTING_APPROVED = 'listing_approved';
    const LISTING_REJECTED = 'listing_rejected';
    const LISTING_EXPIRED = 'listing_expired';
    const LISTING_DELETED = 'listing_deleted';
    const LISTING_SUSPENDED = 'listing_suspended';
    const LISTING_ARCHIVED = 'listing_archived';
    const LISTING_STATUS_CHANGED = 'listing_status_changed';
    const LISTING_VIEWS_MILESTONE = 'listing_views_milestone';
    const LISTING_PRICE_UPDATED = 'listing_price_updated';
    const LISTING_FAVORITED = 'listing_favorited';
    const LISTING_REPORTED = 'listing_reported';
    const LISTING_PREMIUM_EXPIRING = 'listing_premium_expiring';
    const LISTING_COMMENT_RECEIVED = 'listing_comment_received';

    // Şikayyətlər
    const COMPLAINT_REPLY = 'complaint_reply';
    const COMPLAINT_RESOLVED = 'complaint_resolved';
    const COMPLAINT_REJECT = 'complaint_reject';
    const COMPLAINT_CLOSED = 'complaint_closed';
    const COMPLAINT_PROGRESS = 'complaint_progress';

    // Yeni əlavə ediləcək tiplər
    const EMAIL_VERIFIED = 'email_verified';           // Email təsdiqləndi
    const ACCOUNT_BLOCKED = 'account_blocked';         // Hesab bloklandı
    const PAYMENT_RECEIVED = 'payment_received';       // Ödəniş alındı
    const PAYMENT_FAILED = 'payment_failed';          // Ödəniş uğursuz oldu
    const BALANCE_UPDATED = 'balance_updated';        // Balans yeniləndi
    const SUBSCRIPTION_EXPIRING = 'subscription_expiring'; // Abunəlik bitir
    const CHAT_REQUEST = 'chat_request';              // Söhbət tələbi
    const MESSAGE_READ = 'message_read';              // Mesaj oxundu
    const SECURITY_ALERT = 'security_alert';          // Təhlükəsizlik xəbərdarlığı
    const SUSPICIOUS_LOGIN = 'suspicious_login';       // Şübhəli giriş
    const MAINTENANCE_SCHEDULED = 'maintenance_scheduled'; // Planlı texniki işlər
    const SYSTEM_ERROR = 'system_error';              // Sistem xətası
    const API_LIMIT_WARNING = 'api_limit_warning';    // API limit xəbərdarlığı

    public static function getDescription($value): string
    {
        return match ($value) {
            self::WELCOME => t('enums.notification_types.welcome'),
            self::PASSWORD_RESET => t('enums.notification_types.password_reset'),
            self::LOGIN_ALERT => t('enums.notification_types.login_alert'),
            self::PROFILE_UPDATE => t('enums.notification_types.profile_update'),
            self::NEW_MESSAGE => t('enums.notification_types.new_message'),
            self::SYSTEM_ALERT => t('enums.notification_types.system_alert'),
            self::REFERRAL_BONUS_ADDED => t('enums.notification_types.referral_bonus_added'),
            self::REFERRAL_COMPLETED => t('enums.notification_types.referral_completed'),
            // Elanla bağlı notification tipləri
            self::LISTING_CREATED => t('enums.notification_types.listing_created'),
            self::LISTING_APPROVED => t('enums.notification_types.listing_approved'),
            self::LISTING_REJECTED => t('enums.notification_types.listing_rejected'),
            self::LISTING_EXPIRED => t('enums.notification_types.listing_expired'),
            self::LISTING_DELETED => t('enums.notification_types.listing_deleted'),
            self::LISTING_VIEWS_MILESTONE => t('enums.notification_types.listing_views_milestone'),
            self::LISTING_PRICE_UPDATED => t('enums.notification_types.listing_price_updated'),
            self::LISTING_FAVORITED => t('enums.notification_types.listing_favorited'),
            self::LISTING_REPORTED => t('enums.notification_types.listing_reported'),
            self::LISTING_PREMIUM_EXPIRING => t('enums.notification_types.listing_premium_expiring'),
            self::LISTING_COMMENT_RECEIVED => t('enums.notification_types.listing_comment_received'),
            self::LISTING_STATUS_CHANGED => t('enums.notification_types.listing_status_changed'),
            self::LISTING_SUSPENDED => t('enums.notification_types.listing_suspended'),
            self::COMPLAINT_REPLY => t('enums.notification_types.complaint_reply'),
            self::COMPLAINT_RESOLVED => t('enums.notification_types.complaint_resolved'),
            self::COMPLAINT_REJECT => t('enums.notification_types.complaint_reject'),
            self::COMPLAINT_CLOSED => t('enums.notification_types.complaint_closed'),
            self::COMPLAINT_PROGRESS => t('enums.notification_types.complaint_progress'),
            self::EMAIL_VERIFIED => t('enums.notification_types.email_verified'),
            self::ACCOUNT_BLOCKED => t('enums.notification_types.account_blocked'),
            self::PAYMENT_RECEIVED => t('enums.notification_types.payment_received'),
            self::PAYMENT_FAILED => t('enums.notification_types.payment_failed'),
            self::BALANCE_UPDATED => t('enums.notification_types.balance_updated'),
            self::SUBSCRIPTION_EXPIRING => t('enums.notification_types.subscription_expiring'),
            self::CHAT_REQUEST => t('enums.notification_types.chat_request'),
            self::MESSAGE_READ => t('enums.notification_types.message_read'),
            self::SECURITY_ALERT => t('enums.notification_types.security_alert'),
            self::SUSPICIOUS_LOGIN => t('enums.notification_types.suspicious_login'),
            self::MAINTENANCE_SCHEDULED => t('enums.notification_types.maintenance_scheduled'),
            self::SYSTEM_ERROR => t('enums.notification_types.system_error'),
            self::API_LIMIT_WARNING => t('enums.notification_types.api_limit_warning'),
            default => self::getKey($value),
        };
    }

    public static function getLongDescription($value): string
    {
        return match ($value) {
            self::WELCOME => t('enums.notification_types.welcome_description'),
            self::PASSWORD_RESET => t('enums.notification_types.password_reset_description'),
            self::LOGIN_ALERT => t('enums.notification_types.login_alert_description'),
            self::PROFILE_UPDATE => t('enums.notification_types.profile_update_description'),
            self::NEW_MESSAGE => t('enums.notification_types.new_message_description'),
            self::SYSTEM_ALERT => t('enums.notification_types.system_alert_description'),
            self::REFERRAL_BONUS_ADDED => t('enums.notification_types.referral_bonus_added_description'),
            self::REFERRAL_COMPLETED => t('enums.notification_types.referral_completed_description'),
            // Elanla bağlı notification tipləri
            self::LISTING_CREATED => t('enums.notification_types.listing_created_description'),
            self::LISTING_APPROVED => t('enums.notification_types.listing_approved_description'),
            self::LISTING_REJECTED => t('enums.notification_types.listing_rejected_description'),
            self::LISTING_EXPIRED => t('enums.notification_types.listing_expired_description'),
            self::LISTING_DELETED => t('enums.notification_types.listing_deleted_description'),
            self::LISTING_STATUS_CHANGED => t('enums.notification_types.listing_status_changed_description'),
            self::LISTING_SUSPENDED => t('enums.notification_types.listing_suspended_description'),
            self::LISTING_ARCHIVED => t('enums.notification_types.listing_archived_description'),
            self::LISTING_VIEWS_MILESTONE => t('enums.notification_types.listing_views_milestone_description'),
            self::LISTING_PRICE_UPDATED => t('enums.notification_types.listing_price_updated_description'),
            self::LISTING_FAVORITED => t('enums.notification_types.listing_favorited_description'),
            self::LISTING_REPORTED => t('enums.notification_types.listing_reported_description'),
            self::LISTING_PREMIUM_EXPIRING => t('enums.notification_types.listing_premium_expiring_description'),
            self::LISTING_COMMENT_RECEIVED => t('enums.notification_types.listing_comment_received_description'),
            self::COMPLAINT_REPLY => t('enums.notification_types.complaint_reply_description'),
            self::COMPLAINT_RESOLVED => t('enums.notification_types.complaint_resolved_description'),
            self::COMPLAINT_REJECT => t('enums.notification_types.complaint_reject_description'),
            self::COMPLAINT_CLOSED => t('enums.notification_types.complaint_closed_description'),
            self::COMPLAINT_PROGRESS => t('enums.notification_types.complaint_progress_description'),
            self::EMAIL_VERIFIED => t('enums.notification_types.email_verified_description'),
            self::ACCOUNT_BLOCKED => t('enums.notification_types.account_blocked_description'),
            self::PAYMENT_RECEIVED => t('enums.notification_types.payment_received_description'),
            self::PAYMENT_FAILED => t('enums.notification_types.payment_failed_description'),
            self::BALANCE_UPDATED => t('enums.notification_types.balance_updated_description'),
            self::SUBSCRIPTION_EXPIRING => t('enums.notification_types.subscription_expiring_description'),
            self::CHAT_REQUEST => t('enums.notification_types.chat_request_description'),
            self::MESSAGE_READ => t('enums.notification_types.message_read_description'),
            self::SECURITY_ALERT => t('enums.notification_types.security_alert_description'),
            self::SUSPICIOUS_LOGIN => t('enums.notification_types.suspicious_login_description'),
            self::MAINTENANCE_SCHEDULED => t('enums.notification_types.maintenance_scheduled_description'),
            self::SYSTEM_ERROR => t('enums.notification_types.system_error_description'),
            self::API_LIMIT_WARNING => t('enums.notification_types.api_limit_warning_description'),
            default => self::getDescription($value),
        };
    }

    /**
     * Hər notification tipi üçün default məlumatları təyin edir.
     * Bu metod notification yaradılarkən base data strukturunu müəyyən edir.
     */
    public static function getDefaultData($value): array
    {
        // Əsas bildiriş növləri üçün default data
        $commonData = match ($value) {
            self::WELCOME => [
                'title' => 'Xoş gəlmisiniz!',
                'icon' => 'hand-wave',
                'action_url' => '/dashboard'
            ],
            self::PASSWORD_RESET => [
                'title' => 'Şifrə Yeniləndi',
                'icon' => 'key',
                'action_url' => '/profile/security'
            ],
            self::LOGIN_ALERT => [
                'title' => 'Yeni Giriş',
                'icon' => 'shield-alert',
                'action_url' => '/profile/security'
            ],

            // Əsas notification tiplərinin davamı...
            default => []
        };

        // Elan bildirişləri üçün default data
        $listingData = match ($value) {
            self::LISTING_CREATED => [
                'icon' => 'file-plus',
                'color' => 'blue'
            ],
            self::LISTING_APPROVED => [
                'icon' => 'check-circle',
                'color' => 'green'
            ],
            self::LISTING_REJECTED => [
                'icon' => 'x-circle',
                'color' => 'red'
            ],
            self::LISTING_EXPIRED => [
                'icon' => 'clock',
                'color' => 'orange'
            ],
            self::LISTING_DELETED => [
                'icon' => 'trash',
                'color' => 'red'
            ],
            self::LISTING_VIEWS_MILESTONE => [
                'icon' => 'trending-up',
                'color' => 'purple'
            ],
            self::LISTING_PRICE_UPDATED => [
                'icon' => 'currency-dollar',
                'color' => 'blue'
            ],
            self::LISTING_FAVORITED => [
                'icon' => 'heart',
                'color' => 'pink'
            ],
            self::LISTING_REPORTED => [
                'icon' => 'flag',
                'color' => 'red'
            ],
            self::LISTING_PREMIUM_EXPIRING => [
                'icon' => 'star',
                'color' => 'yellow'
            ],
            self::LISTING_COMMENT_RECEIVED => [
                'icon' => 'message-circle',
                'color' => 'blue'
            ],
            self::LISTING_STATUS_CHANGED => [
                'icon' => 'refresh-cw',
                'color' => 'blue'
            ],
            self::LISTING_SUSPENDED => [
                'icon' => 'pause-circle',
                'color' => 'orange'
            ],
            self::LISTING_ARCHIVED => [
                'icon' => 'archive',
                'color' => 'gray'
            ],
            self::EMAIL_VERIFIED => [
                'icon' => 'check-circle',
                'color' => 'green'
            ],
            self::ACCOUNT_BLOCKED => [
                'icon' => 'x-circle',
                'color' => 'red'
            ],
            self::PAYMENT_RECEIVED => [
                'icon' => 'currency-dollar',
                'color' => 'green'
            ],
            self::PAYMENT_FAILED => [
                'icon' => 'x-circle',
                'color' => 'red'
            ],
            self::BALANCE_UPDATED => [
                'icon' => 'currency-dollar',
                'color' => 'blue'
            ],
            self::SUBSCRIPTION_EXPIRING => [
                'icon' => 'clock',
                'color' => 'orange'
            ],
            self::CHAT_REQUEST => [
                'icon' => 'chat',
                'color' => 'blue'
            ],
            self::MESSAGE_READ => [
                'icon' => 'check-circle',
                'color' => 'green'
            ],
            self::SECURITY_ALERT => [
                'icon' => 'shield-alert',
                'color' => 'red'
            ],
            self::SUSPICIOUS_LOGIN => [
                'icon' => 'shield-alert',
                'color' => 'red'
            ],
            self::MAINTENANCE_SCHEDULED => [
                'icon' => 'wrench',
                'color' => 'blue'
            ],
            self::SYSTEM_ERROR => [
                'icon' => 'x-circle',
                'color' => 'red'
            ],
            self::API_LIMIT_WARNING => [
                'icon' => 'warning',
                'color' => 'yellow'
            ],
            default => []
        };

        // Bildiriş növünə görə uyğun datanı seçirik
        $specificData = str_contains($value, 'listing_') ? $listingData : $commonData;

        // Default başlıq və mesajı əlavə edirik
        return array_merge([
            'title' => self::getDescription($value),
            'message' => self::getLongDescription($value)
        ], $specificData[$value] ?? []);
    }

    /**
     * Hər notification tipi üçün default prioriteti təyin edir.
     * Bu metod notification yaradılarkən prioriteti müəyyən edir.
     */
    public static function getDefaultPriority($value): string
    {
        return match ($value) {
            // Yüksək prioritetli bildirişlər
            self::LOGIN_ALERT,
            self::PASSWORD_RESET,
            self::LISTING_REJECTED,
            self::LISTING_REPORTED,
            self::LISTING_DELETED,
            self::LISTING_SUSPENDED,
            self::LISTING_PREMIUM_EXPIRING => NotificationPriorityEnum::HIGH,

            // Aşağı prioritetli bildirişlər
            self::LISTING_VIEWS_MILESTONE,
            self::LISTING_FAVORITED,
            self::LISTING_ARCHIVED,
            self::LISTING_COMMENT_RECEIVED => NotificationPriorityEnum::LOW,

            // Default olaraq normal prioritet
            default => NotificationPriorityEnum::NORMAL
        };
    }

}
