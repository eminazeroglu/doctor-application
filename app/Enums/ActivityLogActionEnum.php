<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class ActivityLogActionEnum extends Enum
{
    const CREATED = 'created';
    const UPDATED = 'updated';
    const DELETED = 'deleted';
    const RESTORED = 'restored';
    const LOGIN = 'login';
    const LOGIN_FAILED = 'login_failed';
    const LOGIN_BLOCKED = 'login_blocked';
    const LOGIN_SUCCESS = 'login_success';
    const LOGIN_ERROR = 'login_error';
    const LOGOUT = 'logout';
    const REGISTER = 'register';
    const SOCIAL_LOGIN = 'social_login';
    const VIEWED = 'viewed';
    const DOWNLOADED = 'downloaded';
    const EXPORTED = 'exported';
    const IMPORTED = 'imported';
    const SERVER_ERROR = 'server_error';
    const AUTH_FAILED = 'auth_failed';
    const REFERRAL_BONUS_ADDED = 'referral_bonus_added';
    const REFERRAL_REJECTED = 'referral_rejected';
    const REFERRAL_COMPLETED = 'referral_completed';
    const REFERRAL_CODE_GENERATED = 'referral_code_generated';


    // Qeydiyyat əməliyyatları üçün
    const REGISTER_BLOCKED = 'register_blocked';
    const REGISTER_ERROR = 'register_error';

    // Şifrə yeniləmə əməliyyatları üçün
    const PASSWORD_RESET_SUCCESS = 'password_reset_success';
    const PASSWORD_RESET_FAILED = 'password_reset_failed';
    const PASSWORD_RESET_ERROR = 'password_reset_error';

    // Email təsdiqləmə əməliyyatları üçün
    const EMAIL_VERIFY_SUCCESS = 'email_verify_success';
    const EMAIL_VERIFY_FAILED = 'email_verify_failed';
    const EMAIL_VERIFY_ERROR = 'email_verify_error';

    // Image
    const IMAGE_UPLOAD_SUCCESS = 'image_upload_success';
    const IMAGE_UPLOAD_ERROR = 'image_upload_error';
    const IMAGE_DELETE_SUCCESS = 'image_delete_success';
    const IMAGE_DELETE_ERROR = 'image_delete_error';
    // API əməliyyatları üçün
    const API_READ = 'api_read';
    const API_CREATE = 'api_create';
    const API_UPDATE = 'api_update';
    const API_DELETE = 'api_delete';
    const API_ERROR = 'api_error';
    const API_OTHER = 'api_other';
    const REFERRAL_LIMIT_EXCEEDED = 'referral_limit_exceeded';
    const UNAUTHORIZED_API_ACCESS = 'unauthorized_api_access';

    // Database əməliyyatları üçün
    const DATABASE_ERROR = 'database_error';
    const FOREIGN_KEY_ERROR = 'foreign_key_error';
    const DUPLICATE_ENTRY = 'duplicate_entry';

    // HTTP əməliyyatları üçün
    const NOT_FOUND = 'not_found';
    const VALIDATION_ERROR = 'validation_error';
    const UNAUTHORIZED = 'unauthorized';
    const FORBIDDEN = 'forbidden';

    public static function getDescription($value): string
    {
        return match ($value) {
            self::CREATED => t('enums.activity_log.action.created'),
            self::UPDATED => t('enums.activity_log.action.updated'),
            self::DELETED => t('enums.activity_log.action.deleted'),
            self::RESTORED => t('enums.activity_log.action.restored'),
            self::LOGIN => t('enums.activity_log.action.login'),
            self::LOGIN_FAILED => t('enums.activity_log.action.login_failed'),
            self::LOGIN_BLOCKED => t('enums.activity_log.action.login_blocked'),
            self::LOGIN_SUCCESS => t('enums.activity_log.action.login_success'),
            self::LOGIN_ERROR => t('enums.activity_log.action.login_error'),
            self::LOGOUT => t('enums.activity_log.action.logout'),
            self::AUTH_FAILED => t('enums.activity_log.action.auth_failed'),
            self::REFERRAL_BONUS_ADDED => t('enums.activity_log.action.referral_bonus_added'),
            self::REFERRAL_REJECTED => t('enums.activity_log.action.referral_rejected'),
            self::REFERRAL_COMPLETED => t('enums.activity_log.action.referral_completed'),
            self::REFERRAL_CODE_GENERATED => t('enums.activity_log.action.referral_code_generated'),
            self::REGISTER => t('enums.activity_log.action.register'),
            self::SOCIAL_LOGIN => t('enums.activity_log.action.social_login'),
            self::VIEWED => t('enums.activity_log.action.viewed'),
            self::DOWNLOADED => t('enums.activity_log.action.downloaded'),
            self::EXPORTED => t('enums.activity_log.action.exported'),
            self::IMPORTED => t('enums.activity_log.action.imported'),
            self::SERVER_ERROR => t('enums.activity_log.action.server_error'),
            self::REGISTER_BLOCKED => t('enums.activity_log.action.register_blocked'),
            self::REGISTER_ERROR => t('enums.activity_log.action.register_error'),
            self::PASSWORD_RESET_SUCCESS => t('enums.activity_log.action.password_reset_success'),
            self::PASSWORD_RESET_FAILED => t('enums.activity_log.action.password_reset_failed'),
            self::PASSWORD_RESET_ERROR => t('enums.activity_log.action.password_reset_error'),
            self::EMAIL_VERIFY_SUCCESS => t('enums.activity_log.action.email_verify_success'),
            self::EMAIL_VERIFY_FAILED => t('enums.activity_log.action.email_verify_failed'),
            self::EMAIL_VERIFY_ERROR => t('enums.activity_log.action.email_verify_error'),
            self::IMAGE_UPLOAD_SUCCESS => t('enums.activity_log.action.image_upload_success'),
            self::IMAGE_UPLOAD_ERROR => t('enums.activity_log.action.image_upload_error'),
            self::IMAGE_DELETE_SUCCESS => t('enums.activity_log.action.image_delete_success'),
            self::IMAGE_DELETE_ERROR => t('enums.activity_log.action.image_delete_error'),
            self::API_READ => t('enums.activity_log.action.api_read'),
            self::API_CREATE => t('enums.activity_log.action.api_create'),
            self::API_UPDATE => t('enums.activity_log.action.api_update'),
            self::API_DELETE => t('enums.activity_log.action.api_delete'),
            self::API_ERROR => t('enums.activity_log.action.api_error'),
            self::API_OTHER => t('enums.activity_log.action.api_other'),
            self::REFERRAL_LIMIT_EXCEEDED => t('enums.activity_log.action.referral_limit_exceeded'),
            self::UNAUTHORIZED_API_ACCESS => t('enums.activity_log.action.unauthorized_api_access'),
            self::DATABASE_ERROR => t('enums.activity_log.action.database_error'),
            self::FOREIGN_KEY_ERROR => t('enums.activity_log.action.foreign_key_error'),
            self::DUPLICATE_ENTRY => t('enums.activity_log.action.duplicate_entry'),
            self::NOT_FOUND => t('enums.activity_log.action.not_found'),
            self::VALIDATION_ERROR => t('enums.activity_log.action.validation_error'),
            self::UNAUTHORIZED => t('enums.activity_log.action.unauthorized'),
            self::FORBIDDEN => t('enums.activity_log.action.forbidden'),
            default => self::getKey($value),
        };
    }
}
