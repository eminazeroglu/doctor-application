<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\ApiController;
use App\Http\Resources\Admin\PaymentResource;
use App\Services\Module\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends ApiController
{
    public function __construct(PaymentService $service)
    {
        parent::__construct($service, 'payment');
        $this->setResource(PaymentResource::class);
    }

    public function show(mixed $id): JsonResponse
    {
        if (!$this->authorizeAction('read')) {
            return response()->json(['message' => $this->forbiddenMessage], 403);
        }

        $data = $this->service->findById($id);
        return response()->json($this->toResource($data));
    }

    public function complete(Request $request, $id): JsonResponse
    {
        $payment = $this->service->markAsCompleted($id, $request->input('transaction_data'));
        return response()->json($this->toResource($payment));
    }

    public function fail(Request $request, $id): JsonResponse
    {
        $payment = $this->service->markAsFailed($id, $request->input('reason'));
        return response()->json($this->toResource($payment));
    }

    public function refund(Request $request, $id): JsonResponse
    {
        $payment = $this->service->refund($id, $request->input('reason'));
        return response()->json($this->toResource($payment));
    }

    public function retry($id): JsonResponse
    {
        $payment = $this->service->retry($id);
        return response()->json($this->toResource($payment));
    }
}
