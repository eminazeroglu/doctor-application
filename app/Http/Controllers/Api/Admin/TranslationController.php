<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Requests\Translation\TranslateRequest;
use App\Http\Resources\Admin\TranslationResource;
use App\Services\Module\TranslationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TranslationController
{
    public TranslationService $service;
    public string $permission = 'translation';
    public string $forbiddenMessage = 'You are not authorized to do this operation.';

    protected function authorizeAction(string $ability): bool
    {
        return request()->user()->hasPermission($this->permission . '_' . $ability);
    }

    public function __construct(TranslationService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request): JsonResponse
    {
        if ($this->authorizeAction('read')) {
            $data = $this->service->paginateAndFilter();
            return response()->json([
                'data' => TranslationResource::collection($data),
                'total' => $data->total()
            ]);
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    public function save(TranslateRequest $request): JsonResponse
    {
        if ($this->authorizeAction('create') || $this->authorizeAction('update')) {
            $data = $this->service->save($request);
            return response()->json($data);
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    public function destroy($id): JsonResponse
    {
        if ($this->authorizeAction('delete')) {
            $data = $this->service->destroy($id);
            return response()->json($data);
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }
}
