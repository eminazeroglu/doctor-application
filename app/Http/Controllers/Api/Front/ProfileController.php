<?php

namespace App\Http\Controllers\Api\Front;

use App\Exceptions\BaseException;
use App\Http\Controllers\Controller;
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
        $user = $this->profileService->getUserProfile(auth()->user());

        return response()->json([
            'user' => new UserResource($user),
            'message' => t('notification.profile.retrieved_successfully')
        ]);
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
            'address' => 'nullable|string|max:500',
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
}
