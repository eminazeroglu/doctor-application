<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\ApiController;
use App\Http\Resources\Admin\UserResource;
use App\Services\Module\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends ApiController
{
    public function __construct(UserService $service)
    {
        parent::__construct($service, 'user');
        $this->setResource(UserResource::class);
    }

    public function commonRules(): array
    {
        return [
            'name' => ['required'],
            'surname' => ['required'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore(request()->id)],
            'password' => request()->id ? ['sometimes'] : ['required'],
            'role_id' => ['required', 'exists:roles,id'],
            'gender' => ['required', 'in:male,female'],
        ];
    }

    public function action(Request $request, $id): JsonResponse
    {
        if ($this->authorizeAction('status')) {
            $data = $this->service->changeStatus($id, $request->all());
            return response()->json($this->toResource($data));
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Dashboard məlumatlarını qaytarır.
     *
     * @return JsonResponse
     */
    public function dashboard(): JsonResponse
    {
        // İcazə yoxlaması (ApiController-dan gəlir)
        if (!$this->authorizeAction('read')) {
            return response()->json(['message' => $this->forbiddenMessage], 403);
        }

        // UserService-dən dashboard məlumatlarını alırıq
        $data = $this->service->getDashboardData();

        // JSON cavabını qaytarırıq
        return response()->json($data);
    }
}
