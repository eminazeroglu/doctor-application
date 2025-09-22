<?php

namespace App\Http\Controllers;

use App\Traits\Controller\HasAuthorizesRequests;
use App\Traits\Controller\HasDispatchesEvents;
use App\Traits\Controller\HasHandlesResources;
use App\Traits\Controller\HasValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

abstract class ApiController extends Controller
{
    use HasAuthorizesRequests, HasValidatesRequests, HasHandlesResources, HasDispatchesEvents;

    protected $service;
    protected bool $hasShowResource = false;

    public function __construct($service, ?string $permission = null, ?string $formRequestClass = null)
    {
        $this->service = $service;
        $this->setPermission($permission);
        $this->setFormRequestClass($formRequestClass);
    }

    /**
     * Bütün resursları siyahılayır
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        if (!$this->authorizeAction('read')) {
            return $this->forbidden();
        }

        $data = $this->service->paginateAndFilter();
        return response()->json([
            'data' => $this->toResource($data),
            'total' => (request()->has('tree') || request()->has('full')) ? $data->count() : $data->total()
        ]);
    }

    /**
     * Konkret resursu id ilə göstərir
     *
     * @param mixed $id
     * @return JsonResponse
     */
    public function show(mixed $id): JsonResponse
    {
        if (!$this->authorizeAction('read')) {
            return $this->forbidden();
        }

        $data = $this->service->findById($id);
        return response()->json($this->hasShowResource ? $this->toResource($data) : $data);
    }

    /**
     * Yeni resurs yaradır
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function store(Request $request): JsonResponse
    {
        if (!$this->authorizeAction('create')) {
            return $this->forbidden();
        }

        // Dinamik olaraq validasiya: ya FormRequest ilə, ya da qaydalar ilə
        $this->validateRequest($request, $this->storeRules(), $this->storeMessages());

        $data = $this->service->create($request->all());

        // Hadisəni işə salırıq
        $this->dispatchEvent('store', $data);

        return response()->json($this->toResource($data), 201);
    }

    /**
     * Mövcud resursu yeniləyir
     *
     * @param Request $request
     * @param mixed $id
     * @return JsonResponse
     * @throws ValidationException
     */
    public function update(Request $request, mixed $id): JsonResponse
    {
        if (!$this->authorizeAction('update')) {
            return $this->forbidden();
        }

        // Dinamik olaraq validasiya: ya FormRequest ilə, ya da qaydalar ilə
        $this->validateRequest($request, $this->updateRules(), $this->updateMessages());

        $data = $this->service->update($id, $request->all());

        // Hadisəni işə salırıq
        $this->dispatchEvent('update', $data);

        return response()->json($this->toResource($data));
    }

    /**
     * Resursu silir
     *
     * @param mixed $id
     * @return JsonResponse
     */
    public function destroy(mixed $id): JsonResponse
    {
        if (!$this->authorizeAction('delete')) {
            return $this->forbidden();
        }

        $data = $this->service->delete($id);

        // Hadisəni işə salırıq
        $this->dispatchEvent('destroy', $data);

        return response()->json($data);
    }

    /**
     * Resursun statusunu dəyişdirir
     *
     * @param Request $request
     * @param mixed $id
     * @return JsonResponse
     */
    public function action(Request $request, $id): JsonResponse
    {
        if (!$this->authorizeAction('status')) {
            return $this->forbidden();
        }

        $data = $this->service->changeStatus($id, $request->action);
        return response()->json($this->toResource($data));
    }

    /**
     * Resursların sırasını dəyişdirir
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function order(Request $request): JsonResponse
    {
        if (!$this->authorizeAction('status')) {
            return $this->forbidden();
        }

        $data = $this->service->updateOrder($request);
        return response()->json($data);
    }

    /**
     * Filter məlumatlarını qaytarır
     *
     * @return JsonResponse
     */
    public function filters(): JsonResponse
    {
        if (!$this->authorizeAction('read')) {
            return $this->forbidden();
        }

        $data = $this->service->filters();
        return response()->json($data);
    }

    /**
     * Çoxlu resurslara eyni əməliyyatı tətbiq edir
     *
     * @param string $type
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function bulk(string $type, Request $request): JsonResponse
    {
        if (!$this->authorizeAction('status')) {
            return $this->forbidden();
        }

        $this->validateRequest($request, [
            'ids' => 'required|array',
        ]);

        $data = $this->service->bulk($type, $request);
        return response()->json($data);
    }
}
