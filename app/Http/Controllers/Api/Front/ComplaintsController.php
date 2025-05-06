<?php

namespace App\Http\Controllers\Api\Front;

use App\Enums\ComplaintTypeEnum;
use App\Http\Controllers\ApiController;
use App\Http\Resources\Front\ComplaintResource;
use App\Rules\ImageBase64Rule;
use App\Rules\Base64ImageControlRule;
use App\Services\Module\ComplaintsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ComplaintsController extends ApiController
{
    public function __construct(ComplaintsService $service)
    {
        parent::__construct($service, null);
        $this->setResource(ComplaintResource::class);
    }

    /**
     * Metodun məqsədi: Şikayətlərin yaradılması və cavab yazılması üçün ümumi validasiya qaydalarını təyin edir.
     * Store və reply metodlarında istifadə olunur.
     */
    public function commonRules(): array
    {
        return [
            'complaintable_type' => 'sometimes|required|string|in:' . implode(',', ComplaintTypeEnum::getValues()),
            'complaintable_id' => 'sometimes|required|integer',
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'attachments' => 'nullable|array',
            'attachments.*' => [new ImageBase64Rule, new Base64ImageControlRule],
        ];
    }

    /**
     * Metodun məqsədi: Cari istifadəçinin öz şikayətlərini siyahıya alır.
     * İstifadəçi yalnız öz şikayətlərini görə bilər.
     */
    public function myComplaints(): JsonResponse
    {
        $complaints = $this->service->findByUser(Auth::id());
        return response()->json($this->toResource($complaints));
    }

    /**
     * Metodun məqsədi: Yeni şikayət yaradır (istifadəçi tərəfindən).
     * Validated məlumatları service-ə göndərir və user_id avtomatik əlavə olunur.
     * @throws ValidationException
     */
    public function store(Request $request): JsonResponse
    {
        $data = $this->validateRequest($request, $this->commonRules());
        $data['user_id'] = Auth::id();
        $complaint = $this->service->create($data);
        return response()->json($this->toResource($complaint), 201);
    }

    /**
     * Metodun məqsədi: Müəyyən bir şikayətin detallarını göstərir.
     * Yalnız şikayətin sahibi və ya staff cavabı varsa baxa bilər.
     */
    public function show(mixed $id): JsonResponse
    {
        $complaint = $this->service->repository->findByUuid($id);
        if ($complaint->user_id !== Auth::id() && !$complaint->messages()->where('is_staff_reply', true)->exists()) {
            return response()->json(['message' => 'Bu şikayətə baxa bilməzsiniz'], 403);
        }
        return response()->json($this->toResource($complaint));
    }

    /**
     * Metodun məqsədi: Şikayətə istifadəçi tərəfindən cavab yazır.
     * Yalnız şikayətin sahibi cavab yaza bilər.
     * @throws ValidationException
     */
    public function reply(Request $request, string $uuid): JsonResponse
    {
        $complaint = $this->service->repository->findByUuid($uuid);
        if ($complaint->user_id !== Auth::id()) {
            return response()->json(['message' => 'Bu şikayətə cavab verə bilməzsiniz'], 403);
        }

        $this->validateRequest($request, [
            'message' => 'required|string',
            'attachments' => 'nullable|array',
            'attachments.*' => [new ImageBase64Rule, new Base64ImageControlRule],
        ]);

        $message = $this->service->reply($complaint->id, $request->all());
        return response()->json($this->toResource($message));
    }

}
