<?php

namespace App\Http\Middleware;

use App\Enums\CredentialTypeEnum;
use App\Services\Module\BlockedCredentialService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckBlockedCredentials
{
    protected BlockedCredentialService $blockedCredentialService;

    public function __construct(BlockedCredentialService $blockedCredentialService)
    {
        $this->blockedCredentialService = $blockedCredentialService;
    }

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // IP ünvanını yoxlayırıq
        $clientIp = $request->ip();
        $ipBlock = $this->blockedCredentialService->getBlockInfo(
            CredentialTypeEnum::IP,
            $clientIp
        );

        if ($ipBlock && $ipBlock->isActive()) {
            return $this->blockResponse(
                'IP ünvanınız bloklnıb',
                $ipBlock->reason,
                $ipBlock->remaining_time
            );
        }

        // Əgər auth route-larına müraciət edilirsə, email/telefon yoxlaması edirik
        if ($this->isAuthRoute($request)) {
            // Email yoxlaması
            $email = $request->input('email');
            if ($email) {
                $emailBlock = $this->blockedCredentialService->getBlockInfo(
                    CredentialTypeEnum::Email,
                    $email
                );

                if ($emailBlock && $emailBlock->isActive()) {
                    return $this->blockResponse(
                        'Email ünvanınız bloklanıb',
                        $emailBlock->reason,
                        $emailBlock->remaining_time
                    );
                }
            }

            // Telefon yoxlaması
            $phone = $request->input('phone');
            if ($phone) {
                $phoneBlock = $this->blockedCredentialService->getBlockInfo(
                    CredentialTypeEnum::Phone,
                    $phone
                );

                if ($phoneBlock && $phoneBlock->isActive()) {
                    return $this->blockResponse(
                        'Telefon nömrəniz bloklanıb',
                        $phoneBlock->reason,
                        $phoneBlock->remaining_time
                    );
                }
            }
        }

        return $next($request);
    }

    /**
     * Müraciətin auth route-larına aid olub-olmadığını yoxlayır
     *
     * @param Request $request
     * @return bool
     */
    protected function isAuthRoute(Request $request): bool
    {
        $authRoutes = [
            'login',
            'register',
            'forgot-password',
            'reset-password'
        ];

        return in_array($request->route()->getName(), $authRoutes) ||
            str_starts_with($request->path(), 'api/auth');
    }

    /**
     * Bloklanma halında qaytarılacaq response
     *
     * @param string $message
     * @param string $reason
     * @param string $remainingTime
     * @return Response
     */
    protected function blockResponse(
        string $message,
        string $reason,
        string $remainingTime
    ): Response
    {
        return response()->json([
            'code' => '23000',
            'message' => $message,
            'details' => [
                'reason' => $reason,
                'remaining_time' => $remainingTime
            ]
        ], 403);
    }
}
