<?php

namespace App\Http\Controllers\Api\Front;

use App\Http\Controllers\Controller;
use App\Http\Resources\Front\PageResource;
use App\Services\Module\PageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PageController extends Controller
{
    protected PageService $pageService;

    public function __construct(PageService $pageService)
    {
        $this->pageService = $pageService;
    }

    /**
     * Bütün aktiv səhifələri qaytarır
     */
    public function index(): JsonResponse
    {
        $pages = $this->pageService->findActiveList();

        return response()->json([
            'data' => PageResource::collection($pages)
        ]);
    }

    /**
     * Slug ilə səhifəni qaytarır
     */
    public function show($slug, Request $request): JsonResponse
    {
        $locale = $request->header('Locale', app()->getLocale());
        $page = $this->pageService->findBySlug($slug, $locale);

        return response()->json([
            'data' => new PageResource($page)
        ]);
    }

    /**
     * Növə görə səhifələri qaytarır
     */
    public function byType($type): JsonResponse
    {
        $pages = $this->pageService->findByType($type);

        return response()->json([
            'data' => PageResource::collection($pages)
        ]);
    }
}
