<?php

namespace App\Http\Controllers\Api\Front;

use App\Exceptions\BaseException;
use App\Http\Controllers\Controller;
use App\Http\Resources\Front\DoctorCertificateResource;
use App\Http\Resources\Front\DoctorEducationResource;
use App\Http\Resources\Front\DoctorExperienceResource;
use App\Http\Resources\Front\DoctorLanguageResource;
use App\Http\Resources\Front\DoctorServiceResource;
use App\Http\Resources\Front\UserResource;
use App\Http\Resources\Admin\UserPreferenceResource;
use App\Services\Module\ProfileService;
use App\Traits\Controller\HasValidatesRequests;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    use HasValidatesRequests;

    public ProfileService $profileService;

    public function __construct(ProfileService $profileService)
    {
        $this->profileService = $profileService;
    }

    /**
     * İstifadəçi profil məlumatlarını əldə edir
     *
     * GET /api/app/profile
     */
    public function index(): JsonResponse
    {
        $user = $this->profileService->getUserProfile(auth()->id());

        return response()->json(new UserResource($user));
    }

    /**
     * İstifadəçinin profil məlumatlarını yeniləyir
     *
     * PUT /api/app/profile
     *
     * @throws ValidationException
     */
    public function update(Request $request): JsonResponse
    {
        $formFields = $this->validateRequest($request, [
            'name' => 'required|string|max:255',
            'surname' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:users,phone,' . auth()->id(),
            'photo_path' => 'nullable|string',
            'gender' => 'nullable|in:male,female',
            'birthdate' => 'nullable|date'
        ]);

        $user = $this->profileService->updateProfile(
            auth()->user(),
            $formFields
        );

        return response()->json([
            'user' => new UserResource($user),
            'message' => t('notification.profile.updated_successfully')
        ]);
    }

    /**
     * İstifadəçinin hesab tənzimləmələrini yeniləyir (email və şifrə)
     *
     * PUT /api/app/profile/account
     *
     * @throws ValidationException|BaseException
     */
    public function updateAccount(Request $request): JsonResponse
    {
        // Email və ya şifrə dəyişərkən current_password mütləqdir
        $currentUser = auth()->user();
        $emailChanging = $request->has('email') && $request->input('email') !== $currentUser->email;
        $passwordChanging = $request->has('password') && !empty($request->input('password'));

        $rules = [
            'email' => 'required|string|email|max:255|unique:users,email,' . auth()->id(),
            'password' => 'nullable|string|min:8|confirmed',
        ];

        $messages = [
            'email.required' => t('validation.email.required'),
            'email.email' => t('validation.email.email'),
            'email.unique' => t('validation.email.unique'),
            'password.min' => t('validation.password.min'),
            'password.confirmed' => t('validation.password.confirmed')
        ];

        // Email dəyişirsə və ya şifrə dəyişirsə current_password mütləqdir və düzgün olmalıdır
        if ($emailChanging || $passwordChanging) {
            $rules['current_password'] = [
                'required',
                'string',
                function ($attribute, $value, $fail) use ($currentUser) {
                    if (!Hash::check($value, $currentUser->password)) {
                        $fail(t('validation.current_password.incorrect'));
                    }
                }
            ];
            $messages['current_password.required'] = t('validation.current_password.required');
        }

        $formFields = $this->validateRequest($request, $rules, $messages);

        $user = $this->profileService->updateAccountSettings(
            auth()->user(),
            $formFields
        );

        return response()->json([
            'user' => new UserResource($user),
            'message' => t('notification.profile.account_updated_successfully')
        ]);
    }

    /**
     * İstifadəçi hesabını deaktiv edir
     *
     * POST /api/app/profile/deactivate
     *
     * @throws ValidationException
     * @throws Exception
     */
    public function deactivate(Request $request): JsonResponse
    {
        $formFields = $this->validateRequest($request, [
            'reason' => 'required|in:platform_dislike,no_activity,personal_reasons,other',
            'custom_reason' => 'required_if:reason,other|nullable|string|max:500'
        ]);

        $this->profileService->deactivateAccount(
            auth()->user(),
            $formFields
        );

        return response()->json([
            'message' => t('notification.profile.account_deactivated_successfully')
        ]);
    }

    /**
     * İstifadəçi hesabını silir
     *
     * DELETE /api/app/profile
     *
     * @throws ValidationException|BaseException
     */
    public function destroy(Request $request): JsonResponse
    {
        $formFields = $this->validateRequest($request, [
            'reason' => 'required|in:platform_dislike,no_activity,personal_reasons,other',
            'custom_reason' => 'required_if:reason,other|nullable|string|max:500',
            'password' => 'required|string'
        ]);

        $this->profileService->deleteAccount(
            auth()->user(),
            $formFields
        );

        return response()->json([
            'message' => t('notification.profile.account_deleted_successfully')
        ]);
    }

    /**
     * Profil şəklini yeniləyir
     *
     * POST /api/app/profile/avatar
     *
     * @throws ValidationException
     * @throws Exception
     */
    public function updateAvatar(Request $request): JsonResponse
    {
        $formFields = $this->validateRequest($request, [
            'photo_path' => 'required|string'
        ]);

        $user = $this->profileService->updateAvatar(
            auth()->user(),
            $request->input('photo_path')
        );

        return response()->json([
            'user' => new UserResource($user),
            'message' => t('notification.profile.avatar_updated_successfully')
        ]);
    }

    /**
     * İstifadəçi preferences-lərini əldə edir
     *
     * GET /api/app/profile/preferences
     */
    public function getPreferences(): JsonResponse
    {
        $preferences = $this->profileService->getPreferences(auth()->user());

        return response()->json([
            'preferences' => new UserPreferenceResource($preferences),
            'message' => t('notification.profile.preferences_retrieved_successfully')
        ]);
    }

    /**
     * İstifadəçi preferences-lərini yeniləyir
     *
     * PUT /api/app/profile/preferences
     *
     * @throws ValidationException
     * @throws Exception
     */
    public function updatePreferences(Request $request): JsonResponse
    {
        $formFields = $this->validateRequest($request, [
            'language' => 'nullable|string|in:az,en,ru,tr',
            'dark_mode' => 'nullable|boolean',
            'notification_settings' => 'nullable|array',
            'privacy_settings' => 'nullable|array',
            'timezone' => 'nullable|string',
            'email_frequency' => 'nullable|in:daily,weekly,monthly,never'
        ]);

        $preferences = $this->profileService->updatePreferences(
            auth()->user(),
            $formFields
        );

        return response()->json([
            'preferences' => new UserPreferenceResource($preferences),
            'message' => t('notification.profile.preferences_updated_successfully')
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | HƏKİM XİDMƏTLƏRİ - Doctor Services (Bulk Operations)
    |--------------------------------------------------------------------------
    */

    /**
     * Həkimin bacarıqlarını əldə edir
     * GET /api/app/profile/doctor/services
     * @throws BaseException
     */
    public function getDoctorSkills(): JsonResponse
    {
        $this->ensureUserIsDoctor();

        $skills = $this->profileService->getDoctorSkills(auth()->user()->doctor);

        return response()->json($skills);
    }

    /**
     * Həkimin bütün bacarıqlarını yenilə (bulk update)
     * PUT /api/app/profile/doctor/skills
     * @throws BaseException
     * @throws ValidationException
     */
    public function updateDoctorSkills(Request $request): JsonResponse
    {
        $this->ensureUserIsDoctor();

        $formFields = $this->validateRequest($request, [
            'category_id' => 'required|exists:categories,id',
            'sub_category_id' => 'required|exists:categories,id',
            'attributes' => 'required|array',
            'attributes.*.attribute_id' => 'required|exists:attributes,id',
            'attributes.*.attribute_option_id' => 'nullable|exists:attribute_options,id',
            'attributes.*.value' => 'nullable',
        ]);

        $skills = $this->profileService->syncDoctorSkills(
            auth()->user()->doctor,
            $formFields
        );

        return response()->json($skills);
    }

    /*
    |--------------------------------------------------------------------------
    | HƏKİM TƏHSİL - Doctor Education (Bulk Operations)
    |--------------------------------------------------------------------------
    */

    /**
     * Həkimin təhsil məlumatlarını əldə edir
     * GET /api/app/profile/doctor/educations
     * @throws BaseException
     */
    public function getDoctorEducations(): JsonResponse
    {
        $this->ensureUserIsDoctor();

        $educations = $this->profileService->getDoctorEducations(auth()->user()->doctor);

        return response()->json(DoctorEducationResource::collection($educations));
    }

    /**
     * Həkimin bütün təhsil məlumatlarını yenilə (bulk update)
     * PUT /api/app/profile/doctor/educations
     * @throws BaseException
     * @throws ValidationException
     * @throws Exception
     */
    public function updateDoctorEducations(Request $request): JsonResponse
    {
        $this->ensureUserIsDoctor();

        $formFields = $this->validateRequest($request, [
            'educations' => 'required|array',
            'educations.*.university' => 'required|string|max:255',
            'educations.*.faculty' => 'nullable|string|max:255',
            'educations.*.degree' => 'required|string|max:255',
            'educations.*.specialization' => 'nullable|string|max:255',
            'educations.*.start_date' => 'required|date',
            'educations.*.end_date' => 'nullable|date|after:educations.*.start_date',
            'educations.*.location' => 'nullable|string|max:255',
            'educations.*.description' => 'nullable|string|max:1000',
            'educations.*.is_currently_studying' => 'nullable|boolean',
            'educations.*.document_path' => 'nullable|string',
        ]);

        $educations = $this->profileService->syncDoctorEducations(
            auth()->user()->doctor,
            $formFields['educations']
        );

        return response()->json(DoctorEducationResource::collection($educations));
    }

    /*
    |--------------------------------------------------------------------------
    | HƏKİM İŞ TƏCRÜBƏSİ - Doctor Experience (Bulk Operations)
    |--------------------------------------------------------------------------
    */

    /**
     * Həkimin iş təcrübəsi məlumatlarını əldə edir
     * GET /api/app/profile/doctor/experiences
     * @throws BaseException
     */
    public function getDoctorExperiences(): JsonResponse
    {
        $this->ensureUserIsDoctor();

        $experiences = $this->profileService->getDoctorExperiences(auth()->user()->doctor);
        $experiences = $experiences->map(function ($i) {
            $item = [
                'id' => $i->id,
                'clinic_id' => $i->clinic_id,
                'profession' => $i->profession,
                'work_time' => $i->work_time,
                'services' => $i->services()->pluck('id')->toArray(),
            ];

            if (!$i->clinic_id && $i->custom_clinic && $i->custom_clinic->name) {
                unset($item['clinic_id']);
                $item['custom_clinic'] = $i->custom_clinic;
            }

            return $item;
        });

        return response()->json($experiences);
    }

    /**
     * Həkimin bütün iş təcrübəsi məlumatlarını yenilə (bulk update)
     * PUT /api/app/profile/doctor/experiences
     * @throws BaseException
     * @throws ValidationException
     */
    public function updateDoctorExperiences(Request $request): JsonResponse
    {
        $this->ensureUserIsDoctor();

        $formFields = $this->validateRequest($request, [
            'experiences' => 'required|array',
            'experiences.*.clinic_id' => 'required|exists:clinics,id',
            'experiences.*.profession' => 'required|string|max:255',
            'experiences.*.work_time' => 'nullable|array',
            'experiences.*.work_time.start_day' => 'nullable',
            'experiences.*.work_time.start_time' => 'nullable',
            'experiences.*.work_time.end_day' => 'nullable',
            'experiences.*.work_time.end_time' => 'nullable',
            'experiences.*.services' => 'required|array',
            'experiences.*.services.*' => 'required|exists:services,id',
        ]);

        $this->profileService->syncDoctorExperiences(
            auth()->user()->doctor,
            $formFields['experiences']
        );

        return $this->getDoctorExperiences();
    }

    /*
    |--------------------------------------------------------------------------
    | HƏKİM SERTİFİKATLAR - Doctor Certificates (Bulk Operations)
    |--------------------------------------------------------------------------
    */

    /**
     * Həkimin sertifikatlarını əldə edir
     * GET /api/app/profile/doctor/certificates
     * @throws BaseException
     */
    public function getDoctorCertificates(): JsonResponse
    {
        $this->ensureUserIsDoctor();

        $certificates = $this->profileService->getDoctorCertificates(auth()->user()->doctor);

        return response()->json(DoctorCertificateResource::collection($certificates));
    }

    /**
     * Yeni sertifikat əlavə edir
     * POST /api/app/profile/doctor/certificates
     * @throws BaseException|ValidationException
     */
    public function storeDoctorCertificate(Request $request): JsonResponse
    {
        $this->ensureUserIsDoctor();

        $form = $this->validateRequest($request, [
            'document' => 'required|file|mimes:pdf,png,jpg,jpeg|max:20480',
        ]);

        $certificate = $this->profileService->createDoctorCertificate(
            auth()->user()->doctor,
            $form['document']
        );

        return response()->json([
            'certificate' => new DoctorCertificateResource($certificate),
            'message' => t('notification.certificate.created_successfully')
        ]);
    }

    /**
     * Sertifikatı silir
     * DELETE /api/app/profile/doctor/certificates/{id}
     * @throws BaseException
     */
    public function deleteDoctorCertificate(int $id): JsonResponse
    {
        $this->ensureUserIsDoctor();

        $this->profileService->deleteDoctorCertificate(
            auth()->user()->doctor,
            $id
        );

        return response()->json([
            'message' => t('notification.certificate.deleted_successfully')
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | HELPER METHODS - Köməkçi metodlar
    |--------------------------------------------------------------------------
    */

    /**
     * İstifadəçinin həkim olub-olmadığını yoxlayır
     * @throws BaseException
     */
    private function ensureUserIsDoctor(): void
    {
        if (!auth()->user()->hasDoctor()) {
            throw new BaseException(t('validation.user.not_doctor'), 403);
        }
    }
}
