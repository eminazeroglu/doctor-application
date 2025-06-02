<?php

namespace App\Services\Module;

use App\Enums\GenderEnum;
use App\Enums\UserStatusEnum;
use App\Models\Role;
use App\Models\User;
use App\Repositories\Module\UserRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class UserService
{
    public UserRepository $repository;

    public function __construct(UserRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Yeni resurs yaradır.
     *
     * @param array $data
     * @return User
     */
    public function create(array $data): User
    {
        return $this->repository->create($data);
    }

    /**
     * Mövcud resursu yeniləyir.
     *
     * @param int $id
     * @param array $data
     * @return Model
     */
    public function update(int $id, array $data): Model
    {
        return $this->repository->update($id, $data);
    }

    /**
     * Resursun statusunu dəyişir (məsələn, is_active).
     *
     * @param int $id
     * @param string $statusField
     * @return Model
     */
    public function changeStatus(int $id, array $request): Model
    {
        return $this->repository->statusChange($id, $request);
    }

    /**
     * ID ilə resursu tapır.
     *
     * @param int $id
     * @return Model
     */
    public function findById(int $id): Model
    {
        return $this->repository->findById($id);
    }

    /**
     * Datatable üçün səhifələmə və filtr ilə məlumatları geri qaytarır.
     *
     * @return LengthAwarePaginator
     */
    public function paginateAndFilter(): LengthAwarePaginator
    {
        return $this->repository->paginateAndFilter();
    }

    /**
     * Aktiv resursları geri qaytarır.
     *
     * @return Collection
     */
    public function findActiveList(): Collection
    {
        return $this->repository->findActiveList();
    }

    /**
     * ID ilə resursu silir.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        return $this->repository->delete($id);
    }

    public function filters()
    {
        $permissions = Role::query()->get();
        $genders = collect(GenderEnum::getValues())->map(fn($i) => [
            'id' => $i,
            'name' => GenderEnum::getDescription($i),
        ]);
        $statuses = collect(UserStatusEnum::getValues())->map(fn($i) => [
            'id' => $i,
            'name' => UserStatusEnum::getDescription($i),
        ]);

        return [
            'permissions' => $permissions,
            'genders' => $genders,
            'statuses' => $statuses,
        ];
    }

    /**
     * Dashboard üçün bütün məlumatları toplayır.
     *
     * @return array Dashboard məlumatları
     */
    public function getDashboardData(): array
    {
        return [
            'stats' => $this->getStats(),
            'recent_activity' => $this->getRecentActivity(),
            'activity_logs' => $this->getActivityLogs(),
            'distributions' => $this->getDistributions(),
            'balances' => $this->getBalances(),
            'login_trends' => $this->getLoginTrends(),
            'preferences' => $this->getPreferences(),
        ];
    }

    /**
     * Ümumi statistikaları qaytarır.
     */
    private function getStats(): array
    {
        return [
            'total_users' => $this->repository->countTotalUsers(),
            'active_users' => $this->repository->countActiveUsers(),
            'new_users_24h' => $this->repository->countNewUsersLast24Hours(),
            'unverified_emails' => $this->repository->countUnverifiedEmails(),
            'social_login_users' => $this->repository->countSocialLoginUsers(),
        ];
    }

    /**
     * Son istifadəçi fəaliyyətlərini qaytarır.
     */
    private function getRecentActivity(): array
    {
        return [
            'new_users' => $this->repository->getRecentUsers(5),
            'last_logins' => $this->repository->getRecentLogins(5),
        ];
    }

    /**
     * Referral statistikalarını qaytarır.
     */
    private function getReferralStats(): array
    {
        return [
            'total_earnings' => $this->repository->getTotalReferralEarnings(),
            'active_codes' => $this->repository->countActiveReferralCodes(),
            'last_7_days_referrals' => $this->repository->countReferralsLast7Days(),
        ];
    }

    /**
     * Son fəaliyyət loglarını qaytarır.
     */
    private function getActivityLogs(): array
    {
        return $this->repository->getRecentActivityLogs(5);
    }

    /**
     * Status və gender paylanmasını qaytarır.
     */
    private function getDistributions(): array
    {
        return [
            'status' => $this->repository->getStatusDistribution(),
            'gender' => $this->repository->getGenderDistribution(),
        ];
    }

    /**
     * Balans məlumatlarını qaytarır.
     */
    private function getBalances(): array
    {
        return [
            'total_main_balance' => $this->repository->getTotalMainBalance(),
            'total_referral_balance' => $this->repository->getTotalReferralBalance(),
            'top_users' => $this->repository->getTopUsersByBalance(5),
        ];
    }

    /**
     * Login trendlərini qaytarır.
     */
    private function getLoginTrends(): array
    {
        return [
            'last_7_days' => $this->repository->getLoginTrendLast7Days(),
            'device_types' => $this->repository->getDeviceTypeDistribution(),
        ];
    }

    /**
     * İstifadəçi tərcihlərini qaytarır.
     */
    private function getPreferences(): array
    {
        return [
            'dark_mode_users' => $this->repository->countDarkModeUsers(),
            'light_mode_users' => $this->repository->countLightModeUsers(),
            'top_languages' => $this->repository->getTopLanguages(3),
        ];
    }
}
