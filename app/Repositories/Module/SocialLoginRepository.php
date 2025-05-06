<?php

namespace App\Repositories\Module;

use App\Models\User;
use App\Models\UserSocialLogin;
use App\Repositories\BaseRepository;
use Laravel\Socialite\Two\User as SocialiteUser;

class SocialLoginRepository extends BaseRepository
{
    /**
     * UserSocialLogin modeli ilə repository-ni inisializasiya edirik
     */
    public function __construct(UserSocialLogin $model)
    {
        parent::__construct($model);
    }

    /**
     * Yeni sosial login məlumatlarını yaradır
     *
     * @param User $user - Mövcud və ya yeni yaradılmış istifadəçi
     * @param string $provider - Sosial platforma (google, facebook, və s.)
     * @param SocialiteUser $socialUser - Sosial platformadan gələn istifadəçi məlumatları
     * @return UserSocialLogin
     */
    public function createSocialLogin(User $user, string $provider, SocialiteUser $socialUser): UserSocialLogin
    {
        // Sosial platformadan gələn əlavə məlumatları strukturlaşdırırıq
        $providerData = $this->formatProviderData($socialUser);

        // Yeni sosial login qeydi yaradırıq
        return $this->create([
            'user_id' => $user->id,
            'provider' => $provider,
            'provider_id' => $socialUser->getId(),
            'provider_data' => $providerData
        ]);
    }

    /**
     * Mövcud sosial login məlumatlarını yeniləyir
     *
     * @param User $user - Mövcud istifadəçi
     * @param string $provider - Sosial platforma
     * @param SocialiteUser $socialUser - Yeni sosial məlumatlar
     * @return UserSocialLogin
     */
    public function updateSocialLogin(User $user, string $provider, SocialiteUser $socialUser): UserSocialLogin
    {
        // Əvvəlcə mövcud sosial login qeydini axtarırıq
        $socialLogin = $this->model->where([
            'user_id' => $user->id,
            'provider' => $provider
        ])->first();

        // Yeni məlumatları hazırlayırıq
        $providerData = $this->formatProviderData($socialUser);
        $updateData = [
            'provider_id' => $socialUser->getId(),
            'provider_data' => $providerData
        ];

        if ($socialLogin) {
            // Mövcud qeydi yeniləyirik
            $socialLogin->update($updateData);
            return $socialLogin->fresh();
        }

        // Mövcud qeyd yoxdursa, yenisini yaradırıq
        return $this->createSocialLogin($user, $provider, $socialUser);
    }

    /**
     * Provider ID-yə görə sosial login qeydini tapır
     *
     * @param string $provider - Sosial platforma
     * @param string $providerId - Platformadan gələn unikal ID
     * @return UserSocialLogin|null
     */
    public function findByProviderId(string $provider, string $providerId): ?UserSocialLogin
    {
        return $this->model->where([
            'provider' => $provider,
            'provider_id' => $providerId
        ])->first();
    }

    /**
     * Sosial platformadan gələn raw məlumatları strukturlaşdırır
     *
     * @param SocialiteUser $socialUser
     * @return array
     */
    protected function formatProviderData(SocialiteUser $socialUser): array
    {
        // Əsas məlumatları toplayırıq
        $data = [
            'name' => $socialUser->getName(),
            'email' => $socialUser->getEmail(),
            'avatar' => $socialUser->getAvatar(),
            'nickname' => $socialUser->getNickname(),
        ];

        // Provider-ə xüsusi məlumatları əlavə edirik
        $rawData = $socialUser->getRaw();

        // Google spesifik məlumatlar
        if (isset($rawData['given_name'])) {
            $data['first_name'] = $rawData['given_name'];
            $data['last_name'] = $rawData['family_name'] ?? null;
            $data['locale'] = $rawData['locale'] ?? null;
        }

        // Facebook spesifik məlumatlar
        if (isset($rawData['first_name'])) {
            $data['first_name'] = $rawData['first_name'];
            $data['last_name'] = $rawData['last_name'] ?? null;
            $data['short_name'] = $rawData['short_name'] ?? null;
        }

        // LinkedIn spesifik məlumatlar
        if (isset($rawData['localizedFirstName'])) {
            $data['first_name'] = $rawData['localizedFirstName'];
            $data['last_name'] = $rawData['localizedLastName'] ?? null;
            $data['profile_url'] = $rawData['vanityName'] ?? null;
        }

        return $data;
    }
}
