<?php

namespace App\Services\Module;

use App\Enums\UserStatusEnum;
use App\Exceptions\BaseException;
use App\Mail\WelcomeEmailMail;
use App\Models\User;
use App\Models\UserPreference;
use App\Repositories\Module\UserRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Request;
use Exception;

class ProfileService
{
    public UserRepository $userRepository;
    public ActivityLogService $activityLogService;

    public function __construct(
        UserRepository $userRepository,
        ActivityLogService $activityLogService
    ) {
        $this->userRepository = $userRepository;
        $this->activityLogService = $activityLogService;
    }

    /**
     * İstifadəçi profil məlumatlarını əldə edir
     *
     * @param int $userId
     * @return Model
     */
    public function getUserProfile(int $userId): Model
    {
        return $this->userRepository->with(['preferences', 'patient', 'doctor'])->findById($userId);
    }

    /**
     * İstifadəçinin ümumi məlumatlarını yeniləyir
     *
     * @param int $userId
     * @param array $data
     * @return Model
     * @throws Exception
     */
    public function updateGeneralInfo(int $userId, array $data): Model
    {
        try {
            DB::beginTransaction();

            // İstifadəçini tap
            $user = $this->userRepository->findById($userId);

            // Meta məlumatlarını hazırla
            $metaData = [
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'updated_fields' => array_keys($data)
            ];

            // Köhnə məlumatları saxla (log üçün)
            $oldData = [
                'name' => $user->name,
                'surname' => $user->surname,
                'phone' => $user->phone,
                'address' => $user->address
            ];

            // Base64 şəkil yükləməsi üçün flag təyin et
            if (isset($data['photo_path']) && str_starts_with($data['photo_path'], 'data:image')) {
                $user->setUseBase64(true);
            }

            // İstifadəçi məlumatlarını yenilə
            $user = $this->userRepository->update($userId, $data);

            // Activity log
            $this->activityLogService->log(
                action: 'profile_general_updated',
                model: $user,
                oldData: $oldData,
                newData: $data,
                additionalData: $metaData
            );

            DB::commit();

            return $user;

        } catch (Exception $e) {
            DB::rollBack();

            // Xəta logu
            $this->activityLogService->logError(
                action: 'profile_general_update_error',
                message: $e->getMessage(),
                model: $user ?? null
            );

            throw $e;
        }
    }

    /**
     * İstifadəçinin hesab tənzimləmələrini yeniləyir (email və şifrə)
     *
     * @param User $user
     * @param array $data
     * @return User
     * @throws BaseException
     * @throws Exception
     */
    public function updateAccountSettings(User $user, array $data): User
    {
        try {
            DB::beginTransaction();

            $oldEmail = $user->email;
            $emailChanged = isset($data['email']) && $data['email'] !== $oldEmail;
            $passwordChanged = isset($data['password']) && !empty($data['password']);

            // Email və ya şifrə dəyişərkən current_password mütləqdir
            if ($emailChanged || $passwordChanged) {
                if (empty($data['current_password'])) {
                    throw new BaseException([
                        'current_password' => t('validation.current_password.required')
                    ], 422);
                }

                if (!Hash::check($data['current_password'], $user->password)) {
                    throw new BaseException([
                        'current_password' => t('validation.current_password.incorrect')
                    ], 422);
                }
            }

            // Şifrə dəyişirsə, hash-lə
            if ($passwordChanged) {
                $data['password'] = Hash::make($data['password']);

                // Bütün tokenləri sil (təhlükəsizlik üçün)
                $user->tokens()->delete();
            }

            // Email dəyişibsə verification parametrlərini təyin et
            if ($emailChanged) {
                $data['is_active'] = 0;
                $data['email_verified_at'] = null;
            }

            // current_password və password_confirmation sahələrini sil
            unset($data['current_password']);
            unset($data['password_confirmation']);

            // Məlumatları yenilə
            $user->update($data);

            // Email dəyişibsə verification göndər
            if ($emailChanged) {
                $reactUrl = Request::header('Origin') ?: 'https://your-default-react-app.com';
                Mail::to($user->email)->send(new WelcomeEmailMail($user, $reactUrl));
            }

            // Activity log
            $this->activityLogService->log(
                action: 'account_settings_updated',
                model: $user,
                oldData: ['email' => $oldEmail],
                newData: array_diff_key($data, array_flip(['password'])), // Şifrəni loglamayırıq
                additionalData: [
                    'email_changed' => $emailChanged,
                    'password_changed' => $passwordChanged,
                    'verification_email_sent' => $emailChanged,
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent()
                ]
            );

            DB::commit();

            return $user;

        } catch (Exception $e) {
            DB::rollBack();

            $this->activityLogService->logError(
                action: 'account_settings_update_error',
                message: $e->getMessage(),
                model: $user
            );

            throw $e;
        }
    }

    /**
     * İstifadəçi hesabını deaktiv edir
     *
     * @param User $user
     * @param array $data
     * @throws Exception
     */
    public function deactivateAccount(User $user, array $data): void
    {
        try {
            DB::beginTransaction();

            // Hesabı deaktiv et
            $user->update([
                'is_active' => false
            ]);

            // Bütün tokenləri sil
            $user->tokens()->delete();

            // Deaktivləşdirmə səbəbini saxla
            $this->activityLogService->log(
                action: 'account_deactivated',
                model: $user,
                newData: [
                    'deactivation_reason' => $data['reason'],
                    'custom_reason' => $data['custom_reason'] ?? null,
                    'deactivated_at' => now()
                ],
                additionalData: [
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent()
                ]
            );

            DB::commit();

        } catch (Exception $e) {
            DB::rollBack();

            $this->activityLogService->logError(
                action: 'account_deactivation_error',
                message: $e->getMessage(),
                model: $user
            );

            throw $e;
        }
    }

    /**
     * İstifadəçi hesabını silir
     *
     * @param User $user
     * @param array $data
     * @throws BaseException
     * @throws Exception
     */
    public function deleteAccount(User $user, array $data): void
    {
        try {
            DB::beginTransaction();

            // Şifrəni yoxla
            if (!Hash::check($data['password'], $user->password)) {
                throw new BaseException([
                    'password' => t('validation.password.incorrect')
                ], 422);
            }

            // Silmə səbəbini saxla (user silinməmişdən əvvəl)
            $this->activityLogService->log(
                action: 'account_deletion_requested',
                model: $user,
                newData: [
                    'deletion_reason' => $data['reason'],
                    'custom_reason' => $data['custom_reason'] ?? null,
                    'requested_at' => now()
                ],
                additionalData: [
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent()
                ]
            );

            // Bütün tokenləri sil
            $user->tokens()->delete();

            // Hesabı soft delete et
            $user->delete();

            DB::commit();

        } catch (Exception $e) {
            DB::rollBack();

            $this->activityLogService->logError(
                action: 'account_deletion_error',
                message: $e->getMessage(),
                model: $user
            );

            throw $e;
        }
    }

    /**
     * Profil şəklini yeniləyir
     *
     * @param User $user
     * @param string $photoPath
     * @return User
     * @throws Exception
     */
    public function updateAvatar(User $user, string $photoPath): User
    {
        try {
            DB::beginTransaction();

            // Base64 şəkil yükləməsi üçün flag təyin et
            $user->setUseBase64(true);

            $user->update([
                'photo_path' => $photoPath
            ]);

            $this->activityLogService->log(
                action: 'profile_avatar_updated',
                model: $user,
                additionalData: [
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent()
                ]
            );

            DB::commit();

            return $user;

        } catch (Exception $e) {
            DB::rollBack();

            $this->activityLogService->logError(
                action: 'profile_avatar_update_error',
                message: $e->getMessage(),
                model: $user
            );

            throw $e;
        }
    }

    /**
     * İstifadəçi preferences-lərini əldə edir
     *
     * @param User $user
     * @return UserPreference
     */
    public function getPreferences(User $user): UserPreference
    {
        return $user->preferences ?? new UserPreference();
    }

    /**
     * İstifadəçi preferences-lərini yeniləyir
     *
     * @param User $user
     * @param array $data
     * @return UserPreference
     * @throws Exception
     */
    public function updatePreferences(User $user, array $data): UserPreference
    {
        try {
            DB::beginTransaction();

            $preferences = $user->preferences ?? new UserPreference(['user_id' => $user->id]);

            $oldData = $preferences->exists ? $preferences->toArray() : [];

            $preferences->fill($data);
            $preferences->save();

            // Activity log
            $this->activityLogService->log(
                action: 'user_preferences_updated',
                oldData: $oldData,
                newData: $data,
                additionalData: [
                    'user_id' => $user->id,
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent()
                ]
            );

            DB::commit();

            return $preferences;

        } catch (Exception $e) {
            DB::rollBack();

            $this->activityLogService->logError(
                action: 'user_preferences_update_error',
                message: $e->getMessage()
            );

            throw $e;
        }
    }
}
