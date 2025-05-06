<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\ApiController;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\UserBlockResource;
use App\Services\Module\UserBlockService;
use App\Traits\Controller\HasValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserBlockController extends ApiController
{
    use HasValidatesRequests;

    public function __construct(UserBlockService $service)
    {
        parent::__construct($service, 'user_block');
    }

    /**
     * Bütün istifadəçi bloklarını listələyir
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $request = request();

        $this->validateRequest($request, [
            'search' => 'nullable|string|max:100',
            'blocker_id' => 'nullable|integer|exists:users,id',
            'blocked_id' => 'nullable|integer|exists:users,id',
            'active_only' => 'nullable|boolean',
            'inactive_only' => 'nullable|boolean',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100'
        ]);

        $filters = $request->only([
            'search', 'blocker_id', 'blocked_id', 'active_only', 'inactive_only',
            'date_from', 'date_to', 'page', 'per_page'
        ]);

        $blocks = $this->userBlockService->getAllBlocks($filters);

        return response()->json([
            'success' => true,
            'data' => UserBlockResource::collection($blocks),
            'meta' => [
                'total' => $blocks->total(),
                'per_page' => $blocks->perPage(),
                'current_page' => $blocks->currentPage(),
                'last_page' => $blocks->lastPage()
            ]
        ]);
    }

    /**
     * İstifadəçini admin olaraq bloklayır
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function blockUser(Request $request): JsonResponse
    {
        $this->validateRequest($request, [
            'user_id' => 'required|integer|exists:users,id',
            'blocked_id' => 'required|integer|exists:users,id|different:user_id',
            'reason' => 'nullable|string|max:500'
        ]);

        try {
            $userBlock = $this->userBlockService->blockUser(
                $request->user_id,
                $request->blocked_id,
                $request->reason
            );

            return response()->json([
                'success' => true,
                'data' => new UserBlockResource($userBlock),
                'message' => 'İstifadəçi uğurla bloklandı'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'İstifadəçi bloklanarkən xəta: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * İstifadəçi blokunu admin olaraq ləğv edir
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function unblockUser(Request $request): JsonResponse
    {
        $this->validateRequest($request, [
            'block_id' => 'required|integer|exists:user_blocks,id'
        ]);

        try {
            $result = $this->userBlockService->unblockUser($request->block_id);

            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => 'İstifadəçi bloku uğurla ləğv edildi'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'İstifadəçi blokunu ləğv edərkən xəta baş verdi'
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'İstifadəçi blokunu ləğv edərkən xəta: ' . $e->getMessage()
            ], 500);
        }
    }
}
