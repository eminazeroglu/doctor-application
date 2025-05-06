<?php

namespace App\Services\Module;

use App\Enums\ActivityLogActionEnum;
use App\Enums\SocialProviderEnum;
use App\Enums\UserStatusEnum;
use App\Models\User;
use App\Repositories\Module\SocialLoginRepository;
use Exception;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Facades\Socialite;
use InvalidArgumentException;

class SocialLoginService
{
    /**
     * We use dependency injection to get our required services and repositories.
     * This makes the code more testable and follows SOLID principles.
     */
    public function __construct(
        protected SocialLoginRepository $repository,
        protected AuthService $authService
    ) {}

    /**
     * Generates a redirect URL for social authentication.
     * This method handles the initial step of the OAuth flow.
     *
     * @param string $provider The social provider (google, facebook, etc.)
     * @return string The URL to redirect the user to
     * @throws InvalidArgumentException If the provider is not supported
     */
    public function getRedirectUrl(string $provider): string
    {
        $this->validateProvider($provider);

        return $this->configureSocialite($provider)
            ->stateless()
            ->redirect()
            ->getTargetUrl();
    }

    /**
     * Handles the callback from social provider and processes user authentication.
     * This method is responsible for either logging in an existing user or creating a new one.
     *
     * @param string $provider The social provider
     * @param string $token Access token from the provider
     * @return array Contains token, user data, and whether the user is new
     * @throws Exception If the authentication process fails
     */
    public function handleCallback(string $provider, string $token): array
    {
        $this->validateProvider($provider);

        try {
            DB::beginTransaction();

            $socialUser = $this->getSocialUser($provider, $token);
            $user = $this->findOrCreateUser($provider, $socialUser);

            // Log the social login activity
            $this->logSocialLoginActivity($user, $provider);

            DB::commit();

            return $this->generateAuthResponse($user);

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Validates if the provided social provider is supported.
     *
     * @param string $provider
     * @throws InvalidArgumentException
     */
    private function validateProvider(string $provider): void
    {
        if (!SocialProviderEnum::hasValue($provider)) {
            throw new InvalidArgumentException("Unsupported social provider: {$provider}");
        }
    }

    /**
     * Configures Socialite with provider-specific settings.
     *
     * @param string $provider
     * @return \Laravel\Socialite\Contracts\Provider
     */
    private function configureSocialite(string $provider)
    {
        $socialite = Socialite::driver($provider)
            ->scopes(SocialProviderEnum::getScopes($provider));

        foreach (SocialProviderEnum::getParameters($provider) as $key => $value) {
            $socialite->with([$key => $value]);
        }

        return $socialite;
    }

    /**
     * Retrieves user information from social provider using the access token.
     *
     * @param string $provider
     * @param string $token
     * @return \Laravel\Socialite\Contracts\User
     */
    private function getSocialUser(string $provider, string $token)
    {
        return Socialite::driver($provider)
            ->stateless()
            ->userFromToken($token);
    }

    /**
     * Finds existing user or creates a new one based on social provider data.
     *
     * @param string $provider
     * @param \Laravel\Socialite\Contracts\User $socialUser
     * @return User
     */
    private function findOrCreateUser(string $provider, $socialUser): User
    {
        $user = User::where('email', $socialUser->getEmail())->first();

        return $user
            ? $this->updateExistingUser($user, $provider, $socialUser)
            : $this->createNewUser($provider, $socialUser);
    }

    /**
     * Creates a new user from social provider data.
     *
     * @param string $provider
     * @param \Laravel\Socialite\Contracts\User $socialUser
     * @return User
     */
    private function createNewUser(string $provider, $socialUser): User
    {
        $userData = [
            'email' => $socialUser->getEmail(),
            'name' => $socialUser->getName(),
            'provider' => $provider,
            'provider_id' => $socialUser->getId(),
            'status' => UserStatusEnum::Active,
            'email_verified_at' => now(),
            'password' => bcrypt(str()->random(16))
        ];

        $user = $this->repository->create($userData);
        $this->repository->createSocialLogin($user, $provider, $socialUser);

        return $user;
    }

    /**
     * Updates existing user with social provider data.
     *
     * @param User $user
     * @param string $provider
     * @param \Laravel\Socialite\Contracts\User $socialUser
     * @return User
     */
    private function updateExistingUser(User $user, string $provider, $socialUser): User
    {
        $user->update([
            'provider' => $provider,
            'provider_id' => $socialUser->getId()
        ]);

        $this->repository->updateSocialLogin($user, $provider, $socialUser);

        return $user;
    }

    /**
     * Logs social login activity for the user.
     *
     * @param User $user
     * @param string $provider
     */
    private function logSocialLoginActivity(User $user, string $provider): void
    {
        $user->logActivity(
            action: ActivityLogActionEnum::SOCIAL_LOGIN,
            oldData: ['provider' => $provider]
        );
    }

    /**
     * Generates the authentication response after successful social login.
     *
     * @param User $user
     * @return array
     */
    private function generateAuthResponse(User $user): array
    {
        return [
            'token' => $user->createToken('auth_token')->plainTextToken,
            'user' => $user,
            'is_new' => $user->wasRecentlyCreated
        ];
    }
}
