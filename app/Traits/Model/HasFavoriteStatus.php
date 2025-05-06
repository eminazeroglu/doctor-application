<?php

namespace App\Traits\Model;

use App\Models\ListingFavorite;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

trait HasFavoriteStatus
{
    /**
     * Listing sorğusuna favorite statusunu əlavə edən scope
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeWithFavoriteStatus(Builder $query): Builder
    {
        // Əgər istifadəçi autentifikasiya olunubsa favorite statusunu hesablayırıq
        $table = $this->getTable();

        if (auth()->check()) {
            $userId = auth()->id();

            // Favorite statusunu əlavə etmək üçün subquery
            $favoriteQuery = DB::table('listing_favorites')
                ->select(DB::raw('1'))
                ->whereColumn('listing_favorites.listing_id', "{$table}.id")
                ->where('listing_favorites.user_id', $userId)
                ->limit(1);

            // Alt sorğunu əsas sorğuya əlavə edirik
            $query->selectRaw("{$table}.*")
                ->selectSub("EXISTS ({$favoriteQuery->toSql()})", 'is_favorite')
                ->addBinding($favoriteQuery->getBindings(), 'select');
        } else {
            // Login olmayan istifadəçilər üçün false
            $query->selectRaw("{$table}.*, FALSE as is_favorite");
        }

        return $query;
    }

    /**
     * Listing modelinə və ya kolleksiyaya favorite statusunu əlavə edən statik köməkçi metod
     * Bu metod, artıq mövcud olan modellər üçün istifadə edilir
     *
     * @param mixed $items Bir listing modeli və ya kolleksiyası
     * @return mixed Favorite statusu əlavə edilmiş eyni model və ya kolleksiya
     */
    public static function addFavoriteStatus(mixed $items): mixed
    {
        // Tək model və ya kolleksiya olduğunu müəyyən edirik
        $isSingleModel = !($items instanceof Collection);

        // Tək modeldirsə, kolleksiyaya çeviririk
        if ($isSingleModel) {
            $collection = collect([$items]);
        } else {
            $collection = $items;
        }

        // Boş kolleksiya üçün işləməyə ehtiyac yoxdur
        if ($collection->isEmpty()) {
            return $isSingleModel ? null : $collection;
        }

        // İstifadəçi giriş edibsə favorite statusunu hesablayırıq
        if (auth()->check()) {
            // Bütün listing ID-lərini toplayırıq
            $listingIds = $collection->pluck('id')->toArray();
            $userId = auth()->id();

            // Bir sorğu ilə bütün favorite ID-ləri alırıq
            $favoriteIds = DB::table('listing_favorites')
                ->where('user_id', $userId)
                ->whereIn('listing_id', $listingIds)
                ->pluck('listing_id')
                ->toArray();

            // Hər bir model üçün favorite statusunu təyin edirik
            foreach ($collection as $model) {
                $model->is_favorite = in_array($model->id, $favoriteIds);
            }
        } else {
            // İstifadəçi giriş etməyibsə, bütün modellər üçün favorite false olur
            foreach ($collection as $model) {
                $model->is_favorite = false;
            }
        }

        // Eyni formatda qaytarırıq (tək model və ya kolleksiya)
        return $isSingleModel ? $collection->first() : $collection;
    }

    /**
     * Favorite əlaqəsini təyin edir
     *
     * @return HasMany
     */
    public function favorites(): HasMany
    {
        return $this->hasMany(ListingFavorite::class, 'listing_id');
    }

    /**
     * İstifadəçinin bu elanı favorite-lərdə olub-olmadığını yoxlayır
     * Bu metod səmərəsizdir və yalnız tək elanı yoxlamaq üçün istifadə edilməlidir
     * Çoxlu elanlar üçün scopeWithFavoriteStatus istifadə edin
     *
     * @param int|null $userId İstifadəçi ID-si, null olduqda cari istifadəçi istifadə edilir
     * @return bool
     */
    public function isFavorite(?int $userId = null): bool
    {
        if (!$userId && !auth()->check()) {
            return false;
        }

        $userId = $userId ?? auth()->id();

        return $this->favorites()->where('user_id', $userId)->exists();
    }
}
