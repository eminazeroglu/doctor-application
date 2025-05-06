<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\ApiController;
use App\Http\Resources\Admin\CommentResource;
use App\Services\Module\CommentService;
use Illuminate\Http\JsonResponse;

class CommentController extends ApiController
{
    public function __construct(CommentService $service)
    {
        parent::__construct($service, 'comment');
        $this->setResource(CommentResource::class);
    }

    public function commonRules(): array
    {
        return [
            // Add validation rules for store method
        ];
    }

    public function show(mixed $id): JsonResponse
    {
        if (!$this->authorizeAction('read')) {
            return response()->json(['message' => $this->forbiddenMessage], 403);
        }

        $data = $this->service->findById($id);
        return response()->json(new CommentResource($data));
    }
}
