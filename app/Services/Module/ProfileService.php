<?php

namespace App\Services\Module;

use App\Enums\UserStatusEnum;
use App\Exceptions\BaseException;
use App\Mail\WelcomeEmailMail;
use App\Models\Doctor;
use App\Models\DoctorCertificate;
use App\Models\DoctorClinicService;
use App\Models\DoctorEducation;
use App\Models\DoctorExperience;
use App\Models\DoctorLanguage;
use App\Models\User;
use App\Models\UserPreference;
use App\Repositories\Module\UserRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Request;
use Exception;
use Illuminate\Support\Facades\Schema;

class ProfileService
{
    public UserRepository $userRepository;
    public ActivityLogService $activityLogService;

    public function __construct(
        UserRepository     $userRepository,
        ActivityLogService $activityLogService
    )
    {
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

    /*
    |--------------------------------------------------------------------------
    | DOCTOR SERVICES METHODS - Həkim xidmətləri metodları (Bulk Operations)
    |--------------------------------------------------------------------------
    */

    /**
     * Həkimin bacarıqlarını əldə edir
     */
    public function getDoctorSkills(Doctor $doctor): array
    {
        return [
            'category_id' => $doctor->category_id,
            'sub_category_id' => $doctor->sub_category_id,
            'attributes' => $doctor->attributes,
        ];
    }

    /**
     * Həkimin bütün xidmətlərini sinxronlaşdırır (bulk sync)
     * @throws BaseException|Exception
     */
    public function syncDoctorSkills(Doctor $doctor, array $data): array
    {
        try {
            DB::beginTransaction();

            $doctor->update([
                'category_id' => $data['category_id'],
                'sub_category_id' => $data['sub_category_id'],
            ]);

            $doctor->attributes()->delete();

            $doctor->attributes()->createMany($data['attributes']);

            DB::commit();

            return $this->getDoctorSkills($doctor->fresh());

        } catch (Exception $e) {
            DB::rollBack();

            $this->activityLogService->logError(
                action: 'doctor_services_sync_error',
                message: $e->getMessage(),
                model: $doctor
            );

            throw $e;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DOCTOR EDUCATION METHODS - Həkim təhsil metodları (Bulk Operations)
    |--------------------------------------------------------------------------
    */

    /**
     * Həkimin təhsil məlumatlarını əldə edir
     */
    public function getDoctorEducations(Doctor $doctor): Collection
    {
        return $doctor->educations()->orderBy('start_date', 'desc')->get();
    }

    /**
     * Həkimin bütün təhsil məlumatlarını sinxronlaşdırır (bulk sync)
     * @throws Exception
     */
    public function syncDoctorEducations(Doctor $doctor, array $educationsData): Collection
    {
        try {
            DB::beginTransaction();


            if (count($educationsData) > 0) {

                $doctor->educations()->delete();

                foreach ($educationsData as $educationData) {
                    if (isset($educationData['is_currently_studying']) && $educationData['is_currently_studying']) {
                        $educationData['end_date'] = null;
                    }
                    $doctor->educations()->create($educationData);
                }
            }

            $this->activityLogService->log(
                action: 'doctor_educations_synced',
                model: $doctor,
                newData: $educationsData,
                additionalData: [
                    'processed_count' => count($educationsData),
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent()
                ]
            );

            DB::commit();

            return $doctor->fresh()->educations()->orderBy('start_date', 'desc')->get();

        } catch (Exception $e) {
            DB::rollBack();
            $this->activityLogService->logError(
                action: 'doctor_educations_sync_error',
                message: $e->getMessage(),
                model: $doctor
            );
            throw $e;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DOCTOR EXPERIENCE METHODS - Həkim iş təcrübəsi metodları (Bulk Operations)
    |--------------------------------------------------------------------------
    */

    /**
     * Həkimin iş təcrübəsi məlumatlarını əldə edir
     */
    public function getDoctorExperiences(Doctor $doctor): Collection
    {
        return $doctor->doctorClinics()->with('services')->get();
        return $doctor->experiences()->orderBy('start_date', 'desc')->get();
    }

    /**
     * Həkimin bütün iş təcrübəsi məlumatlarını sinxronlaşdırır (bulk sync)
     * @throws Exception
     */
    public function syncDoctorExperiences(Doctor $doctor, array $experiencesData): Collection
    {
        try {
            DB::beginTransaction();

            $existingExperiences = $doctor->experiences()->get()->keyBy('id');

            foreach ($experiencesData as $experienceData) {
                $action = $experienceData['_action'] ?? 'create';
                $experienceId = $experienceData['id'] ?? null;

                unset($experienceData['_action'], $experienceData['id']);

                switch ($action) {
                    case 'create':
                        $this->createDoctorExperienceRecord($doctor, $experienceData);
                        break;

                    case 'update':
                        if ($experienceId && $existingExperiences->has($experienceId)) {
                            $this->updateDoctorExperienceRecord($existingExperiences[$experienceId], $experienceData);
                        }
                        break;

                    case 'delete':
                        if ($experienceId && $existingExperiences->has($experienceId)) {
                            $existingExperiences[$experienceId]->delete();
                        }
                        break;
                }
            }

            $this->activityLogService->log(
                action: 'doctor_experiences_synced',
                model: $doctor,
                newData: $experiencesData,
                additionalData: [
                    'processed_count' => count($experiencesData),
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent()
                ]
            );

            DB::commit();

            return $doctor->fresh()->experiences()->orderBy('start_date', 'desc')->get();

        } catch (Exception $e) {
            DB::rollBack();
            $this->activityLogService->logError(
                action: 'doctor_experiences_sync_error',
                message: $e->getMessage(),
                model: $doctor
            );
            throw $e;
        }
    }

    private function createDoctorExperienceRecord(Doctor $doctor, array $data): void
    {
        $data['doctor_id'] = $doctor->id;
        if (isset($data['is_current_job']) && $data['is_current_job']) {
            $data['end_date'] = null;
        }
        DoctorExperience::create($data);
    }

    private function updateDoctorExperienceRecord(DoctorExperience $experience, array $data): void
    {
        if (isset($data['is_current_job']) && $data['is_current_job']) {
            $data['end_date'] = null;
        }
        $experience->update($data);
    }

    /*
    |--------------------------------------------------------------------------
    | DOCTOR CERTIFICATE METHODS - Həkim sertifikat metodları (Bulk Operations)
    |--------------------------------------------------------------------------
    */

    /**
     * Həkimin sertifikatlarını əldə edir
     */
    public function getDoctorCertificates(Doctor $doctor): Collection
    {
        return $doctor->certificates()->orderBy('issue_date', 'desc')->get();
    }

    /**
     * Həkimin bütün sertifikatlarını sinxronlaşdırır (bulk sync)
     */
    public function syncDoctorCertificates(Doctor $doctor, array $certificatesData): Collection
    {
        try {
            DB::beginTransaction();

            $existingCertificates = $doctor->certificates()->get()->keyBy('id');

            foreach ($certificatesData as $certificateData) {
                $action = $certificateData['_action'] ?? 'create';
                $certificateId = $certificateData['id'] ?? null;

                unset($certificateData['_action'], $certificateData['id']);

                switch ($action) {
                    case 'create':
                        $this->createDoctorCertificateRecord($doctor, $certificateData);
                        break;

                    case 'update':
                        if ($certificateId && $existingCertificates->has($certificateId)) {
                            $this->updateDoctorCertificateRecord($existingCertificates[$certificateId], $certificateData);
                        }
                        break;

                    case 'delete':
                        if ($certificateId && $existingCertificates->has($certificateId)) {
                            $existingCertificates[$certificateId]->delete();
                        }
                        break;
                }
            }

            $this->activityLogService->log(
                action: 'doctor_certificates_synced',
                model: $doctor,
                newData: $certificatesData,
                additionalData: [
                    'processed_count' => count($certificatesData),
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent()
                ]
            );

            DB::commit();

            return $doctor->fresh()->certificates()->orderBy('issue_date', 'desc')->get();

        } catch (Exception $e) {
            DB::rollBack();
            $this->activityLogService->logError(
                action: 'doctor_certificates_sync_error',
                message: $e->getMessage(),
                model: $doctor
            );
            throw $e;
        }
    }

    private function createDoctorCertificateRecord(Doctor $doctor, array $data): void
    {
        $data['doctor_id'] = $doctor->id;
        DoctorCertificate::create($data);
    }

    private function updateDoctorCertificateRecord(DoctorCertificate $certificate, array $data): void
    {
        $certificate->update($data);
    }

    /*
    |--------------------------------------------------------------------------
    | DOCTOR LANGUAGE METHODS - Həkim dil metodları (Bulk Operations)
    |--------------------------------------------------------------------------
    */

    /**
     * Həkimin dil biliklərini əldə edir
     */
    public function getDoctorLanguages(Doctor $doctor): Collection
    {
        return $doctor->languages()->orderBy('proficiency', 'desc')->get();
    }

    /**
     * Həkimin bütün dil biliklərini sinxronlaşdırır (bulk sync)
     */
    public function syncDoctorLanguages(Doctor $doctor, array $languagesData): Collection
    {
        try {
            DB::beginTransaction();

            $existingLanguages = $doctor->languages()->get()->keyBy('id');

            foreach ($languagesData as $languageData) {
                $action = $languageData['_action'] ?? 'create';
                $languageId = $languageData['id'] ?? null;

                unset($languageData['_action'], $languageData['id']);

                switch ($action) {
                    case 'create':
                        $this->createDoctorLanguageRecord($doctor, $languageData);
                        break;

                    case 'update':
                        if ($languageId && $existingLanguages->has($languageId)) {
                            $this->updateDoctorLanguageRecord($existingLanguages[$languageId], $languageData);
                        }
                        break;

                    case 'delete':
                        if ($languageId && $existingLanguages->has($languageId)) {
                            $existingLanguages[$languageId]->delete();
                        }
                        break;
                }
            }

            $this->activityLogService->log(
                action: 'doctor_languages_synced',
                model: $doctor,
                newData: $languagesData,
                additionalData: [
                    'processed_count' => count($languagesData),
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent()
                ]
            );

            DB::commit();

            return $doctor->fresh()->languages()->orderBy('proficiency', 'desc')->get();

        } catch (Exception $e) {
            DB::rollBack();
            $this->activityLogService->logError(
                action: 'doctor_languages_sync_error',
                message: $e->getMessage(),
                model: $doctor
            );
            throw $e;
        }
    }

    /**
     * @throws BaseException
     */
    private function createDoctorLanguageRecord(Doctor $doctor, array $data): void
    {
        // Həmin dilin artıq mövcud olmadığını yoxlayırıq
        $existingLanguage = DoctorLanguage::where([
            'doctor_id' => $doctor->id,
            'language' => $data['language']
        ])->first();

        if ($existingLanguage) {
            throw new BaseException([
                'language' => t('validation.doctor.language_already_exists')
            ], 422);
        }

        $data['doctor_id'] = $doctor->id;
        DoctorLanguage::create($data);
    }

    /**
     * @throws BaseException
     */
    private function updateDoctorLanguageRecord(DoctorLanguage $language, array $data): void
    {
        // Həmin dilin artıq başqa qeyddə mövcud olmadığını yoxlayırıq
        $existingLanguage = DoctorLanguage::where([
            'doctor_id' => $language->doctor_id,
            'language' => $data['language']
        ])->where('id', '!=', $language->id)->first();

        if ($existingLanguage) {
            throw new BaseException([
                'language' => t('validation.doctor.language_already_exists')
            ], 422);
        }

        $language->update($data);
    }
}
