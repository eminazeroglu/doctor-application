<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * SocialProviderEnum handles all social login provider configurations.
 * This enum centralizes provider-specific settings and validations.
 */
final class SocialProviderEnum extends Enum
{
    /**
     * Supported social login providers.
     * Each constant represents a unique social platform integration.
     */
    const Google = 'google';
    const Facebook = 'facebook';
    const LinkedIn = 'linkedin';
    const Twitter = 'twitter';
    const GitHub = 'github';

    /**
     * Returns human-readable descriptions for each provider.
     * These are useful for UI displays and logging.
     *
     * @param mixed $value The provider value to get description for
     * @return string Human-readable provider name
     */
    public static function getDescription($value): string
    {
        return match ($value) {
            self::Google => 'Google',
            self::Facebook => 'Facebook',
            self::LinkedIn => 'LinkedIn',
            self::Twitter => 'Twitter',
            self::GitHub => 'GitHub',
            default => self::getKey($value),
        };
    }

    /**
     * Defines OAuth scopes required for each provider.
     * Scopes determine what user data we can access.
     *
     * @param mixed $value The provider value to get scopes for
     * @return array List of required OAuth scopes
     */
    public static function getScopes($value): array
    {
        return match ($value) {
            self::Google => [
                'email',
                'profile',
                'openid',
                // Optional additional scopes
                // 'https://www.googleapis.com/auth/user.birthday.read',
                // 'https://www.googleapis.com/auth/user.gender.read',
            ],
            self::Facebook => [
                'email',
                'public_profile',
                // Optional additional scopes
                // 'user_birthday',
                // 'user_gender',
            ],
            self::LinkedIn => [
                'r_liteprofile',
                'r_emailaddress',
                // Optional additional scopes
                // 'w_member_social',
            ],
            self::Twitter => [
                'tweet.read',
                'users.read',
                'offline.access',
            ],
            self::GitHub => [
                'user',
                'user:email',
            ],
            default => [],
        };
    }

    /**
     * Defines additional OAuth parameters for each provider.
     * These parameters customize the authentication flow.
     *
     * @param mixed $value The provider value to get parameters for
     * @return array Additional OAuth parameters
     */
    public static function getParameters(mixed $value): array
    {
        return match ($value) {
            self::Google => [
                'prompt' => 'select_account', // Force account selection
                'access_type' => 'offline',   // Enable refresh token
                'response_type' => 'code',    // Authorization code flow
                'include_granted_scopes' => 'true', // Include previously granted scopes
            ],
            self::Facebook => [
                'auth_type' => 'rerequest',   // Re-request declined permissions
                'display' => 'popup',         // Display mode
                'response_type' => 'code',    // Authorization code flow
            ],
            self::LinkedIn => [
                'response_type' => 'code',    // Authorization code flow
            ],
            self::Twitter => [
                'response_type' => 'code',    // Authorization code flow
                'code_challenge_method' => 'S256', // PKCE protection
            ],
            self::GitHub => [
                'allow_signup' => 'true',     // Allow new user registration
            ],
            default => [],
        };
    }

    /**
     * Returns provider-specific configuration requirements.
     * Used to validate provider setup.
     *
     * @param mixed $value The provider value to get requirements for
     * @return array Configuration requirements
     */
    public static function getRequirements($value): array
    {
        return match ($value) {
            self::Google => [
                'client_id',
                'client_secret',
                'redirect_uri',
            ],
            self::Facebook => [
                'client_id',
                'client_secret',
                'redirect_uri',
                'default_graph_version',
            ],
            self::LinkedIn => [
                'client_id',
                'client_secret',
                'redirect_uri',
            ],
            default => [
                'client_id',
                'client_secret',
                'redirect_uri',
            ],
        };
    }

    /**
     * Returns a comma-separated list of valid providers.
     * Useful for validation rules.
     *
     * @return string Comma-separated provider values
     */
    public static function getValidationString(): string
    {
        return implode(',', self::getValues());
    }

    /**
     * Checks if a provider requires specific configuration.
     *
     * @param string $provider Provider to check
     * @param string $requirement Requirement to check for
     * @return bool Whether the requirement is needed
     */
    public static function requiresConfig(string $provider, string $requirement): bool
    {
        return in_array($requirement, self::getRequirements($provider));
    }

    /**
     * Returns the default scopes for all providers.
     * These are the minimum required scopes for basic functionality.
     *
     * @return array Default scopes by provider
     */
    public static function getDefaultScopes(): array
    {
        return [
            self::Google => ['email', 'profile'],
            self::Facebook => ['email', 'public_profile'],
            self::LinkedIn => ['r_liteprofile', 'r_emailaddress'],
            self::Twitter => ['tweet.read', 'users.read'],
            self::GitHub => ['user', 'user:email'],
        ];
    }
}
