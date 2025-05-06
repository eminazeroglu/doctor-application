<?php

namespace App\Repositories\Module\Concerns\User;

use App\Enums\GenderEnum;
use App\Enums\ReferralStatusEnum;
use App\Enums\UserStatusEnum;
use App\Models\ActivityLog;
use App\Models\Referral;
use App\Models\User;
use App\Models\UserLoginHistory;
use App\Models\UserPreference;
use App\Models\UserSocialLogin;
use Illuminate\Support\Facades\DB;

trait HasStatistics
{
    /**
     * Ümumi istifadəçi sayını qaytarır.
     *
     * @return int
     */
    public function countTotalUsers(): int
    {
        $cacheKey = $this->getCacheKey('countTotalUsers');
        return $this->remember($cacheKey, function () {
            return $this->model->query()
                ->where('is_system', false)
                ->count();
        });
    }

    /**
     * Aktiv istifadəçi sayını qaytarır.
     *
     * @return int
     */
    public function countActiveUsers(): int
    {
        $cacheKey = $this->getCacheKey('countActiveUsers');
        return $this->remember($cacheKey, function () {
            return $this->model->query()
                ->where('is_system', false)
                ->where('status', UserStatusEnum::Active)
                ->count();
        });
    }

    /**
     * Son 24 saatda qeydiyyatdan keçən istifadəçi sayını qaytarır.
     *
     * @return int
     */
    public function countNewUsersLast24Hours(): int
    {
        $cacheKey = $this->getCacheKey('countNewUsersLast24Hours');
        return $this->remember($cacheKey, function () {
            return $this->model->query()
                ->where('is_system', false)
                ->where('created_at', '>=', now()->subDay())
                ->count();
        });
    }

    /**
     * E-poçtu təsdiqlənməmiş istifadəçi sayını qaytarır.
     *
     * @return int
     */
    public function countUnverifiedEmails(): int
    {
        $cacheKey = $this->getCacheKey('countUnverifiedEmails');
        return $this->remember($cacheKey, function () {
            return $this->model->query()
                ->where('is_system', false)
                ->whereNull('email_verified_at')
                ->count();
        });
    }

    /**
     * Sosial login ilə qeydiyyatdan keçən istifadəçi sayını qaytarır.
     *
     * @return int
     */
    public function countSocialLoginUsers(): int
    {
        $cacheKey = $this->getCacheKey('countSocialLoginUsers');
        return $this->remember($cacheKey, function () {
            return UserSocialLogin::query()
                ->distinct('user_id')
                ->count('user_id');
        });
    }

    /**
     * Son qeydiyyatdan keçən istifadəçiləri qaytarır.
     *
     * @param int $limit Qaytarılacaq istifadəçi sayı (default: 5)
     * @return array
     */
    public function getRecentUsers(int $limit = 5): array
    {
        $cacheKey = $this->getCacheKey('getRecentUsers', ['limit' => $limit]);
        return $this->remember($cacheKey, function () use ($limit) {
            return $this->model->query()
                ->where('is_system', false)
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get(['id', 'name', 'surname', 'email', 'created_at'])
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'fullname' => $user->fullname,
                        'email' => $user->email,
                        'created_at' => $user->created_at,
                    ];
                })
                ->toArray();
        });
    }

    /**
     * Son loginləri qaytarır (user_login_history cədvəlindən).
     *
     * @param int $limit Qaytarılacaq login sayı (default: 5)
     * @return array
     */
    public function getRecentLogins(int $limit = 5): array
    {
        $cacheKey = $this->getCacheKey('getRecentLogins', ['limit' => $limit]);
        return $this->remember($cacheKey, function () use ($limit) {
            return UserLoginHistory::query()
                ->with('user')
                ->orderBy('logged_in_at', 'desc')
                ->limit($limit)
                ->get(['user_id', 'location', 'ip_address', 'logged_in_at'])
                ->map(function ($login) {
                    return [
                        'user_id' => $login->user_id,
                        'fullname' => $login->user->fullname,
                        'code' => $login->user->code,
                        'ip_address' => $login->ip_address,
                        'location' => $login->location,
                        'logged_in_at' => $login->logged_in_at,
                    ];
                })
                ->toArray();
        });
    }

    /**
     * Ümumi referral qazanclarını qaytarır.
     *
     * @return float
     */
    public function getTotalReferralEarnings(): float
    {
        $cacheKey = $this->getCacheKey('getTotalReferralEarnings');
        return $this->remember($cacheKey, function () {
            return $this->model->query()
                ->where('is_system', false)
                ->sum('referral_balance');
        });
    }

    /**
     * Aktiv referral kodlarının sayını qaytarır.
     *
     * @return int
     */
    public function countActiveReferralCodes(): int
    {
        $cacheKey = $this->getCacheKey('countActiveReferralCodes');
        return $this->remember($cacheKey, function () {
            return $this->model->query()
                ->where('is_system', false)
                ->whereNotNull('referral_code')
                ->count();
        });
    }

    /**
     * Son 7 gündə tamamlanmış referral qeydiyyatlarının sayını qaytarır.
     *
     * @return int
     */
    public function countReferralsLast7Days(): int
    {
        $cacheKey = $this->getCacheKey('countReferralsLast7Days');
        return $this->remember($cacheKey, function () {
            return Referral::query()
                ->where('status', ReferralStatusEnum::Completed)
                ->where('created_at', '>=', now()->subDays(7))
                ->count();
        });
    }

    /**
     * Son istifadəçi fəaliyyət loglarını qaytarır.
     *
     * @param int $limit Qaytarılacaq log sayı (default: 5)
     * @return array
     */
    public function getRecentActivityLogs(int $limit = 5): array
    {
        $cacheKey = $this->getCacheKey('getRecentActivityLogs', ['limit' => $limit]);
        return $this->remember($cacheKey, function () use ($limit) {
            return ActivityLog::query()
                ->where('model_type', User::class)
                ->with('creator:id,name,surname')
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get(['id', 'action', 'model_id', 'status', 'ip_address', 'created_at', 'created_by'])
                ->map(function ($log) {
                    return [
                        'id' => $log->id,
                        'action' => $log->action,
                        'action_description' => $log->action_description,
                        'user_id' => $log->model_id,
                        'status' => $log->status,
                        'status_description' => $log->status_description,
                        'ip_address' => $log->ip_address,
                        'creator' => $log->creator ? $log->creator->fullname : 'System',
                        'timestamp' => $log->created_at,
                    ];
                })
                ->toArray();
        });
    }

    /**
     * İstifadəçilərin status üzrə paylanmasını qaytarır.
     *
     * @return array
     */
    public function getStatusDistribution(): array
    {
        $cacheKey = $this->getCacheKey('getStatusDistribution');
        return $this->remember($cacheKey, function () {
            $counts = $this->model->query()
                ->where('is_system', false)
                ->select('status', DB::raw('COUNT(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();

            // Bütün statusları əhatə etmək üçün sıfırlı dəyərləri əlavə edirik
            return [
                UserStatusEnum::Active => [
                    'name' => UserStatusEnum::getDescription(UserStatusEnum::Active),
                    'value' => $counts[UserStatusEnum::Active] ?? 0
                ],
                UserStatusEnum::Inactive => [
                    'name' => UserStatusEnum::getDescription(UserStatusEnum::Inactive),
                    'value' => $counts[UserStatusEnum::Inactive] ?? 0
                ],
                UserStatusEnum::PendingMail => [
                    'name' => UserStatusEnum::getDescription(UserStatusEnum::PendingMail),
                    'value' => $counts[UserStatusEnum::PendingMail] ?? 0
                ],
                UserStatusEnum::PendingProfile => [
                    'name' => UserStatusEnum::getDescription(UserStatusEnum::PendingProfile),
                    'value' => $counts[UserStatusEnum::PendingProfile] ?? 0
                ],
                UserStatusEnum::Block => [
                    'name' => UserStatusEnum::getDescription(UserStatusEnum::Block),
                    'value' => $counts[UserStatusEnum::Block] ?? 0
                ],
            ];
        });
    }

    /**
     * İstifadəçilərin gender üzrə paylanmasını qaytarır.
     *
     * @return array
     */
    public function getGenderDistribution(): array
    {
        $cacheKey = $this->getCacheKey('getGenderDistribution');
        return $this->remember($cacheKey, function () {
            $counts = $this->model->query()
                ->where('is_system', false)
                ->select('gender', DB::raw('COUNT(*) as count'))
                ->groupBy('gender')
                ->pluck('count', 'gender')
                ->toArray();

            // Bütün gender dəyərlərini və null üçün sıfırlı dəyərləri əlavə edirik
            return [
                GenderEnum::Male => [
                    'name' => GenderEnum::getDescription(GenderEnum::Male),
                    'value' => $counts[GenderEnum::Male] ?? 0
                ],
                GenderEnum::Female => [
                    'name' => GenderEnum::getDescription(GenderEnum::Female),
                    'value' => $counts[GenderEnum::Female] ?? 0
                ],
                'unknown' => [
                    'name' => 'Unknown',
                    'value' => $counts[null] ?? 0
                ],
            ];
        });
    }

    /**
     * Ümumi əsas balansın cəmini qaytarır.
     *
     * @return float
     */
    public function getTotalMainBalance(): float
    {
        $cacheKey = $this->getCacheKey('getTotalMainBalance');
        return $this->remember($cacheKey, function () {
            return $this->model->query()
                ->where('is_system', false)
                ->sum('main_balance');
        });
    }

    /**
     * Ümumi referral balansın cəmini qaytarır.
     *
     * @return float
     */
    public function getTotalReferralBalance(): float
    {
        $cacheKey = $this->getCacheKey('getTotalReferralBalance');
        return $this->remember($cacheKey, function () {
            return $this->model->query()
                ->where('is_system', false)
                ->sum('referral_balance');
        });
    }

    /**
     * Ən çox balansa malik istifadəçiləri qaytarır.
     *
     * @param int $limit Qaytarılacaq istifadəçi sayı (default: 3)
     * @return array
     */
    public function getTopUsersByBalance(int $limit = 3): array
    {
        $cacheKey = $this->getCacheKey('getTopUsersByBalance', ['limit' => $limit]);
        return $this->remember($cacheKey, function () use ($limit) {
            return $this->model->query()
                ->where('is_system', false)
                ->where(function ($query) {
                    $query->where('main_balance', '>', 0)
                        ->orWhere('referral_balance', '>', 0);
                })
                ->orderByRaw('GREATEST(main_balance, referral_balance) DESC')
                ->limit($limit)
                ->get(['id', 'name', 'surname', 'photo_path', 'main_balance', 'referral_balance'])
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'photo' => $user->photo,
                        'fullname' => $user->fullname,
                        'main_balance' => $user->main_balance,
                        'referral_balance' => $user->referral_balance,
                    ];
                })
                ->toArray();
        });
    }

    /**
     * Son 7 günün giriş trendlərini qaytarır.
     *
     * @return array
     */
    public function getLoginTrendLast7Days(): array
    {
        $cacheKey = $this->getCacheKey('getLoginTrendLast7Days');
        return $this->remember($cacheKey, function () {
            $counts = UserLoginHistory::query()
                ->where('logged_in_at', '>=', now()->subDays(7))
                ->select(DB::raw('DATE(logged_in_at) as date'), DB::raw('COUNT(*) as count'))
                ->groupBy('date')
                ->orderBy('date', 'asc')
                ->pluck('count', 'date')
                ->toArray();

            // Son 7 günü əhatə etmək üçün sıfırlı dəyərləri əlavə edirik
            $result = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = now()->subDays($i)->toDateString();
                $result[] = [
                    'date' => $date,
                    'count' => $counts[$date] ?? 0,
                ];
            }

            return $result;
        });
    }

    /**
     * Cihaz növləri üzrə paylanmanı qaytarır (faizlə).
     *
     * @return array
     */
    public function getDeviceTypeDistribution(): array
    {
        $cacheKey = $this->getCacheKey('getDeviceTypeDistribution');
        return $this->remember($cacheKey, function () {
            $total = UserLoginHistory::query()->count();

            if ($total === 0) {
                return []; // Login yoxdursa boş array qaytarırıq
            }

            $counts = UserLoginHistory::query()
                ->select('device_type', DB::raw('COUNT(*) as count'))
                ->groupBy('device_type')
                ->pluck('count', 'device_type')
                ->toArray();

            $result = [];
            foreach ($counts as $deviceType => $count) {
                $percentage = round(($count / $total) * 100, 2);
                $result[$deviceType ?? 'unknown'] = $percentage . '%';
            }

            return $result;
        });
    }

    /**
     * Dark mode istifadə edən istifadəçi sayını qaytarır.
     *
     * @return int
     */
    public function countDarkModeUsers(): int
    {
        $cacheKey = $this->getCacheKey('countDarkModeUsers');
        return $this->remember($cacheKey, function () {
            return UserPreference::query()
                ->where('dark_mode', true)
                ->count();
        });
    }

    /**
     * Light mode istifadə edən istifadəçi sayını qaytarır.
     *
     * @return int
     */
    public function countLightModeUsers(): int
    {
        $cacheKey = $this->getCacheKey('countLightModeUsers');
        return $this->remember($cacheKey, function () {
            return UserPreference::query()
                ->where('dark_mode', false)
                ->count();
        });
    }

    /**
     * Ən populyar dilləri qaytarır.
     *
     * @param int $limit Qaytarılacaq dil sayı (default: 3)
     * @return array
     */
    public function getTopLanguages(int $limit = 3): array
    {
        $cacheKey = $this->getCacheKey('getTopLanguages', ['limit' => $limit]);
        return $this->remember($cacheKey, function () use ($limit) {
            return UserPreference::query()
                ->select('language', DB::raw('COUNT(*) as count'))
                ->groupBy('language')
                ->orderBy('count', 'desc')
                ->limit($limit)
                ->pluck('count', 'language')
                ->toArray();
        });
    }
}
