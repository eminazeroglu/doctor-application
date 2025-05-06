<?php

namespace App\Repositories\Module\Concerns\User;

use App\Models\Listing;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Arr;

trait HasFront
{
    public ?Authenticatable $user;

    public function initializeUser(): void
    {
        $this->user = auth()->user();
    }

    public function fetchFavorites()
    {
        return $this->user
            ->favoritedListings()
            ->when(request()->has('category'), function ($query) {
                $query->whereRelation('category', 'slug', request()->category);
            })
            ->withFull()
            ->get();
    }

    public function favoriteAction($uuid): true
    {
        $listing = Listing::whereUuid($uuid)->firstOrFail();
        $isFavorite =  $this->user
            ->favorites()
            ->where('listing_id', $listing->id)
            ->exists();

        if ($isFavorite) {
            $this->user->favorites()->where('listing_id', $listing->id)->delete();
            $listing->decrement('favorites_count');
        }
        else {
            $this->user->favorites()->create([
                'listing_id' => $listing->id,
            ]);
            $listing->increment('favorites_count');
        }

        return true;
    }

    /**
     * User Listings
     */
    public function fetchListingsByStatus($status)
    {
        return $this->user->listings()
            ->where('status', $status)
            ->with('category')
            ->withGeneratedName()
            ->paginate();
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

    /**
     * Payment History
     * */
    public function fetchPaymentHistories()
    {
        return $this->user->payments()
            ->paginate();
    }

    /**
     * Company Gallery
     * */
    public function fetchCompanyGalleries()
    {
        return $this->user->company->getGalleriesUrls();
    }

    /**
     * Company Gallery Delete
     * */
    public function fetchCompanyGalleryDelete($path): true
    {
        $fileName = Arr::last(explode('/', $path));
        return $this->user->company->deleteGalleryPhotos($fileName, 'galleries');
    }

    /**
     * Company Gallery Upload
     * */
    public function fetchCompanyGalleryUpload($photo)
    {
        return $this->user->company->uploadGalleryPhotos([$photo], 'galleries');
    }
}
