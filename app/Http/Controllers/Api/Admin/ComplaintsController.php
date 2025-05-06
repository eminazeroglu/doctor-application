<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\ComplaintStatusEnum;
use App\Http\Controllers\ApiController;
use App\Http\Resources\Admin\ComplaintResource;
use App\Rules\Base64ImageControlRule;
use App\Rules\ImageBase64Rule;
use App\Services\Module\ComplaintsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ComplaintsController extends ApiController
{
    public function __construct(ComplaintsService $service)
    {
        parent::__construct($service, 'complaint');
        $this->setResource(ComplaintResource::class);
    }

    public function commonRules(): array
    {
        return [
            'user_id' => 'sometimes|required|exists:users,id',
            'complaintable_type' => 'sometimes|required|string|in:user,company,listing',
            'complaintable_id' => 'sometimes|required|integer',
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'attachments' => 'nullable|array',
            'attachments.*' => [new ImageBase64Rule, new Base64ImageControlRule],
            'status' => 'sometimes|string|in:pending,processing,resolved,rejected,closed',
            'resolution_note' => 'nullable|string',
            'resolved_by' => 'nullable|exists:users,id',
        ];
    }

    public function show(mixed $id): JsonResponse
    {
        if (!$this->authorizeAction('read')) {
            return response()->json(['message' => $this->forbiddenMessage], 403);
        }

        $data = $this->service->findById($id);
        return response()->json($this->toResource($data));
    }

    /**
     * Metodun məqsədi: Şikayəti statuslarını dəyişməkdir.
     * @throws ValidationException
     */
    public function status(Request $request, $id): JsonResponse
    {
        if (!$this->authorizeAction('status')) {
            return response()->json(['message' => $this->forbiddenMessage], 403);
        }

        $this->validateRequest($request, [
            'status' => ['required', 'in:' . implode(',', ComplaintStatusEnum::getValues())],
            'resolution_note' => [request()->status === ComplaintStatusEnum::Resolved ? 'required' : 'nullable'],
        ]);

        $data = $this->service->status($id, $request->all());
        return response()->json($this->toResource($data));
    }

    /**
     * Metodun məqsədi: Şikayətə admin tərəfindən cavab yazır.
     * Mesaj və əlavələri qeydə alır.
     * @throws ValidationException
     */
    public function reply(Request $request, $id): JsonResponse
    {
        if (!$this->authorizeAction('reply')) {
            return response()->json(['message' => $this->forbiddenMessage], 403);
        }

        $this->validateRequest($request, [
            'message' => 'required|string',
            'attachments' => 'nullable|array',
            'attachments.*' => [new ImageBase64Rule, new Base64ImageControlRule],
        ]);

        $data = $this->service->reply($id, $request->all());
        return response()->json($this->toResource($data));
    }

    /**
     * Metodun məqsədi: Şikayətlərin statistikalarını qaytarır.
     * Statuslara görə sayları göstərir.
     */
    public function stats(): JsonResponse
    {
        if (!$this->authorizeAction('read')) {
            return response()->json(['message' => $this->forbiddenMessage], 403);
        }

        $stats = $this->service->stats();
        return response()->json($stats);
    }
}
