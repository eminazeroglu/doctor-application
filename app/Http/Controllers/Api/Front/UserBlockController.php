<?php

namespace App\Http\Controllers\Api\Front;

use App\Http\Controllers\Controller;
use App\Services\Module\UserBlockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserBlockController extends Controller
{
    protected UserBlockService $userBlockService;

    public function __construct(UserBlockService $userBlockService)
    {
        $this->userBlockService = $userBlockService;
    }

    /**
     * İstifadəçini blok edir
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function blockUser(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer|exists:users,id',
            'reason' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $userBlock = $this->userBlockService->blockUser(
                $request->user_id,
                $request->reason
            );

            return response()->json([
                'success' => true,
                'message' => 'İstifadəçi uğurla bloklandı'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getCode() >= 400 ? $e->getCode() : 500);
        }
    }

    /**
     * İstifadəçi blokunu ləğv edir
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function unblockUser(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->userBlockService->unblockUser($request->user_id);

            return response()->json([
                'success' => true,
                'message' => 'İstifadəçi bloku uğurla ləğv edildi'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getCode() >= 400 ? $e->getCode() : 500);
        }
    }

    /**
     * Bloklanmış istifadəçilər siyahısını qaytarır
     *
     * @return JsonResponse
     */
    public function getBlockedUsers(): JsonResponse
    {
        $blockedUsers = $this->userBlockService->getBlockedUsers();

        return response()->json([
            'success' => true,
            'data' => $blockedUsers
        ]);
    }
}
