<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\UpdateEmailRequest;
use App\Http\Requests\User\UpdatePasswordRequest;
use App\Http\Requests\User\UpdatePreferencesRequest;
use App\Http\Requests\User\UpdateProfileRequest;
use App\Http\Resources\Admin\AuthResource;
use App\Http\Resources\Admin\UserPreferenceResource;
use App\Services\Module\ProfileService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="User Profile",
 *     description="API Endpoints for User Profile management"
 * )
 */
class ProfileController extends Controller
{
    protected $profileService;

    public function __construct(ProfileService $profileService)
    {
        $this->profileService = $profileService;
    }

    public function getProfile(Request $request): JsonResponse
    {
        $user = $request->user();
        return response()->json(new AuthResource($user));
    }

    /**
     * @throws Exception
     */
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $this->profileService->updateGeneralInfo($request->user()->id, $request->validated(), false);
        return response()->json(new AuthResource($user));
    }

    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $this->profileService->updatePassword($request->user(), $request->validated());
        return response()->json(['message' => 'Şifrə uğurla dəyişdirildi']);
    }

    public function updateEmail(UpdateEmailRequest $request): JsonResponse
    {
        $this->profileService->updateEmail($request->user(), $request->validated());
        return response()->json(['message' => 'Email uğurla dəyişdirildi']);
    }

    public function getPreferences(Request $request): JsonResponse
    {
        $preferences = $this->profileService->getPreferences($request->user());
        return response()->json(new UserPreferenceResource($preferences));
    }

    public function updatePreferences(UpdatePreferencesRequest $request): JsonResponse
    {
        $preferences = $this->profileService->updatePreferences($request->user(), $request->validated());
        return response()->json(new UserPreferenceResource($preferences));
    }
}
