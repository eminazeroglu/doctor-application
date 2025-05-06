<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\ApiController;
use App\Services\Module\PermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PermissionController extends ApiController
{
    public function __construct(PermissionService $service)
    {
        parent::__construct($service, 'permission');
    }

    public function commonRules(): array
    {
        return [
            'name' => ['required', 'string']
        ];
    }

    public function groupedPermissions($id): JsonResponse
    {
        if ($this->authorizeAction('read')) {
            $data = $this->service->fetchGroupedPermissions($id);
            return response()->json($data);
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    public function updatePermissions(Request $request, $id): JsonResponse
    {
        if ($this->authorizeAction('create')) {
            $data = $this->service->updatePermissions($id, $request->all());
            return response()->json($data);
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }
}
