<?php

namespace App\Http\Middleware;

use App\Enums\UserStatusEnum;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UserControl
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => t('notification.user_not_found')], 401);
        }

        if ($user->status === UserStatusEnum::Block) {
            return response()->json(['message' => t('notification.user_status.block')], 403);
        }
        else if ($user->status === UserStatusEnum::Inactive) {
            return response()->json(['message' => t('notification.user_status.inactive')], 403);
        }
        else if ($user->status === UserStatusEnum::PendingMail) {
            return response()->json(['message' => t('notification.user_status.pending_mail')], 403);
        }
        else if ($user->status === UserStatusEnum::PendingProfile) {
            return response()->json(['message' => t('notification.user_status.pending_profile')], 403);
        }

        return $next($request);
    }
}
