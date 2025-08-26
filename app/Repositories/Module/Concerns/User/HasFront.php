<?php

namespace App\Repositories\Module\Concerns\User;

use Illuminate\Contracts\Auth\Authenticatable;

trait HasFront
{
    public ?Authenticatable $user;

    public function initializeUser(): void
    {
        $this->user = auth()->user();
    }

    /**
     * Balance
     * */
    public function fetchBalance(): array
    {
        $balance = $this->user->main_balance;
        $referralBalance = $this->user->referral_balance;
        return [
            'balance' => $balance,
            'referral_balance' => $referralBalance,
        ];
    }

    /**
     * Referral Link
     * */
    public function fetchReferral(): array
    {
        $referralLinks = $this->user->getReferralLink();
        $referralStats = $this->user->getReferralStats();
        $referralSetting = setting('referral.rewards.referrer_amount');

        return [
            'referral_links' => $referralLinks,
            'referral_stats' => $referralStats,
            'referral_amount' => $referralSetting,
        ];
    }
}
