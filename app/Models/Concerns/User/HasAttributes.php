<?php

namespace App\Models\Concerns\User;

use App\Enums\GenderEnum;
use App\Enums\UserStatusEnum;
use Illuminate\Database\Eloquent\Casts\Attribute;

trait HasAttributes
{
    /*
    |--------------------------------------------------------------------------
    | ATTRIBUTES - Atributlar
    |--------------------------------------------------------------------------
    */
    public function fullname(): Attribute
    {
        return new Attribute(
            get: fn() => $this->name . ' ' . $this->surname
        );
    }

    public function genderText(): Attribute
    {
        return new Attribute(
            get: fn() => $this->gender ? GenderEnum::getDescription($this->gender) : ''
        );
    }

    public function statusText(): Attribute
    {
        return new Attribute(
            get: fn() => $this->status ? UserStatusEnum::getDescription($this->status) : ''
        );
    }

    public function photo(): Attribute
    {
        return new Attribute(
            get: fn() => $this->getImageUrl('photo_path')
        );
    }

    public function totalExperienceYears(): Attribute
    {
        return new Attribute(
            get: function () {
                $earliestStartDate = $this->experience()
                    ->orderBy('start_date')
                    ->first()?->start_date;

                if (!$earliestStartDate) {
                    return 0;
                }
                return $earliestStartDate->diffInYears(now());
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES - Sorğu filtərləri
    |--------------------------------------------------------------------------
    */
    public function scopeByUser($query)
    {
        return $query->whereRelation('role', 'id', 1);
    }

    public function scopeFullName($query, $value)
    {
        return $query->where(function ($q) use ($value) {
            $q->whereRaw("CONCAT(name, ' ', surname) LIKE ?", ["%$value%"])
                ->orWhereRaw("CONCAT(surname, ' ', name) LIKE ?", ["%$value%"]);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | HELPER METHODS - Köməkçi metodlar
    |--------------------------------------------------------------------------
    */

    /**
     * Verilmiş istifadəçinin blok edilib-edilmədiyini yoxlayır
     */
    public function hasBlocked(int $userId): bool
    {
        return $this->blockedUsers()->where('blocked_id', $userId)->exists();
    }

    /**
     * Verilmiş istifadəçi tərəfindən blok edilib-edilmədiyini yoxlayır
     */
    public function isBlockedBy(int $userId): bool
    {
        return $this->blockedByUsers()->where('blocker_id', $userId)->exists();
    }
}
