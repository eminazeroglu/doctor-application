<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\PaymentServiceKeyEnum;
use App\Enums\PaymentServiceOptionTypeEnum;
use App\Enums\PaymentServiceTypeEnum;
use App\Http\Controllers\ApiController;
use App\Services\Module\PaymentServiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PaymentServiceController extends ApiController
{
    public function __construct(PaymentServiceService $service)
    {
        parent::__construct($service, 'payment_service');
    }

    public function commonRules(): array
    {
        return [
            'key_name' => ['required', 'in:' . implode(',', PaymentServiceKeyEnum::getValues())],
            'type' => ['required', 'in:' . implode(',', PaymentServiceTypeEnum::getValues())],
            'option_type' => ['required', 'in:' . implode(',', PaymentServiceOptionTypeEnum::getValues())],
        ];
    }

    public function options($id): JsonResponse
    {
        if ($this->authorizeAction('read')) {
            $data = $this->service->findByIdOptions($id);
            return response()->json($data);
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * @throws ValidationException
     */
    public function saveOption(Request $request, $id): JsonResponse
    {
        if ($this->authorizeAction('read')) {
            $this->validateRequest($request, [
                'value' => ['required'],
                'amount' => ['required']
            ]);
            $data = $this->service->saveOption($id, $request->all());
            return response()->json($data);
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }
}
