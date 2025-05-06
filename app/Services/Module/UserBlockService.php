<?php

namespace App\Services\Module;

use App\Models\User;
use App\Models\UserBlock;
use App\Exceptions\BaseException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class UserBlockService
{
    /**
     * İstifadəçini blok edir
     *
     * @param int $userId Blok ediləcək istifadəçinin ID-si
     * @param string|null $reason Bloklama səbəbi (ixtiyari)
     * @return UserBlock
     * @throws BaseException
     */
    public function blockUser(int $userId, ?string $reason = null): UserBlock
    {
        $currentUser = Auth::user();

        // Özünü bloklamağa cəhd edirsə
        if ($currentUser->id === $userId) {
            throw new BaseException('Özünüzü bloklaya bilməzsiniz', 400);
        }

        // Blok ediləcək istifadəçi var mı?
        $blockedUser = User::find($userId);
        if (!$blockedUser) {
            throw new BaseException('İstifadəçi tapılmadı', 404);
        }

        // Əvvəlcədən blok edilmiş ola bilər
        if ($currentUser->hasBlocked($userId)) {
            throw new BaseException('Bu istifadəçi artıq bloklanıb', 409);
        }

        // Blok yaradırıq
        return UserBlock::create([
            'blocker_id' => $currentUser->id,
            'blocked_id' => $userId,
            'reason' => $reason,
            'meta_data' => [
                'blocked_at' => now()->toIso8601String(),
                'blocker_ip' => request()->ip()
            ]
        ]);
    }

    /**
     * İstifadəçi blokunu ləğv edir
     *
     * @param int $userId Bloku ləğv ediləcək istifadəçinin ID-si
     * @return bool
     * @throws BaseException
     */
    public function unblockUser(int $userId): bool
    {
        $currentUser = Auth::user();

        // Blok edilmiş istifadəçi var mı?
        $blockedUser = User::find($userId);
        if (!$blockedUser) {
            throw new BaseException('İstifadəçi tapılmadı', 404);
        }

        // Blok var mı?
        $userBlock = $currentUser->blockedUsers()->where('blocked_id', $userId)->first();
        if (!$userBlock) {
            throw new BaseException('Bu istifadəçi bloklanmamışdır', 404);
        }

        // Bloku silirik (softDelete ilə)
        return $userBlock->delete();
    }

    /**
     * İstifadəçinin blok siyahısını əldə edir
     *
     * @return Collection
     */
    public function getBlockedUsers()
    {
        $currentUser = Auth::user();

        return $currentUser->blockedUsers()
            ->with('blocked:id,name,surname,username,photo_path')
            ->get()
            ->map(function($userBlock) {
                return [
                    'id' => $userBlock->blocked->id,
                    'name' => $userBlock->blocked->name,
                    'surname' => $userBlock->blocked->surname,
                    'fullname' => $userBlock->blocked->name . ' ' . $userBlock->blocked->surname,
                    'username' => $userBlock->blocked->username,
                    'photo' => $userBlock->blocked->photo,
                    'blocked_at' => $userBlock->created_at,
                    'reason' => $userBlock->reason
                ];
            });
    }

    /**
     * İki istifadəçi arasında mesajlaşma imkanı var mı?
     *
     * @param int $userId1
     * @param int $userId2
     * @return array [canMessage, blockedBy]
     */
    public function canMessage(int $userId1, int $userId2): array
    {
        $user1 = User::find($userId1);
        $user2 = User::find($userId2);

        if (!$user1 || !$user2) {
            return [false, null];
        }

        // User 1 bloklamışsa
        if ($user1->hasBlocked($userId2)) {
            return [false, $userId1];
        }

        // User 2 bloklamışsa
        if ($user2->hasBlocked($userId1)) {
            return [false, $userId2];
        }

        // Heç bir blok yoxdursa
        return [true, null];
    }
}
