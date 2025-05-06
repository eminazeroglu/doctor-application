<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Resources\Admin\AuthResource;
use App\Services\Module\AuthService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function register(RegisterRequest $request): JsonResponse
    {
       $this->authService->register($request->validated());
        return response()->json(['message' => 'Hesabınız uğurla yaradıldı!. Təstiq üçün email addressinə link göndərildi']);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->validated());
        return response()->json($result);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());
        return response()->json(['message' => 'Hesabdan uğurla çıxdı']);
    }

    public function user(Request $request): JsonResponse
    {
        return response()->json(new AuthResource($request->user()));
    }

    public function refresh(Request $request): JsonResponse
    {
        $token = $this->authService->refreshToken($request->user());
        return response()->json(['token' => $token]);
    }

    /**
     * @throws Exception
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $this->authService->sendResetLinkEmail($request->validated());
        return response()->json(['message' => 'Şifrə dəyişmə linki uğurla göndərildi']);
    }

    /**
     * @throws Exception
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $response = $this->authService->resetPassword($request->validated());
        return response()->json($response);
    }

    /**
     * @throws Exception
     */
    public function verifyEmail(Request $request): JsonResponse
    {
        $this->authService->verifyEmail($request);
        return response()->json(['message' => 'Email uğurla təsdiqləndi']);
    }

    /**
     * @throws Exception
     */
    public function resendVerificationEmail(Request $request): JsonResponse
    {
        $this->authService->resendVerificationEmail($request->user());
        return response()->json(['message' => 'Verification email resent successfully']);
    }
}
