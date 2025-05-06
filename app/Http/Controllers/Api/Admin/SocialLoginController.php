<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\SocialProviderEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AuthResource;
use App\Services\Module\SocialLoginService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SocialLoginController extends Controller
{
    protected SocialLoginService $socialLoginService;

    public function __construct(SocialLoginService $socialLoginService)
    {
        $this->socialLoginService = $socialLoginService;
    }

    /**
     * Sosial login üçün redirect URL-i qaytarır
     */
    public function redirect(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'provider' => ['required', 'string', Rule::in(SocialProviderEnum::getValues())]
        ]);

        $url = $this->socialLoginService->getRedirectUrl($validated['provider']);

        return response()->json(['redirect_url' => $url]);
    }

    /**
     * Sosial platformadan callback-i idarə edir
     */
    public function callback(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'provider' => ['required', 'string', Rule::in(SocialProviderEnum::getValues())],
            'token' => 'required|string'
        ]);

        $result = $this->socialLoginService->handleCallback(
            $validated['provider'],
            $validated['token']
        );

        return response()->json([
            'token' => $result['token'],
            'user' => new AuthResource($result['user']),
            'is_new' => $result['is_new']
        ]);
    }
}
