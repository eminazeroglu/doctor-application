<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\SeoTypeEnum;
use App\Http\Controllers\ApiController;
use App\Http\Resources\Admin\ReferenceResource;
use App\Http\Resources\Admin\SeoLinkResource;
use App\Services\Module\SeoLinkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SeoLinkController extends ApiController
{
    /**
     * SeoController konstruktoru.
     * Servis və icazələri inject edirik.
     */
    public function __construct(SeoLinkService $service)
    {
        parent::__construct($service, 'seo');
        $this->setResource(SeoLinkResource::class);
    }

    /**
     * URL-ə görə SEO məlumatlarını qaytarır.
     * Frontend tərəfdən istifadə olunur.
     */
    public function getByUrl(Request $request): JsonResponse
    {
        $url = $request->get('url');

        // URL-i normalize edirik
        $url = rtrim($url, '/');

        $seoData = $this->service->getByUrl($url);

        if (!$seoData) {
            return response()->json([
                'message' => 'SEO məlumatı tapılmadı'
            ], 404);
        }

        return response()->json([
            'meta_tags' => $seoData->generateMetaTags(),
            'google_preview' => $seoData->getGooglePreviewData(),
            'social_preview' => $seoData->getSocialPreviewData()
        ]);
    }

    /**
     * Meta tag əlavə edir.
     * Admin panel tərəfindən istifadə olunur.
     */
    public function addTag(Request $request, string $uuid): JsonResponse
    {
        $this->validateRequest($request, [
            'group' => 'required|string',
            'type' => 'required|string',
            'content' => 'required|string'
        ]);

        $seo = $this->service->addMetaTag($uuid, $request->all());

        return response()->json([
            'data' => $this->toResource($seo),
            'message' => 'Meta tag uğurla əlavə edildi'
        ]);
    }

    /**
     * Meta tag silir.
     * Admin panel tərəfindən istifadə olunur.
     */
    public function removeTag(Request $request, string $uuid): JsonResponse
    {
        $this->validateRequest($request, [
            'group' => 'required|string',
            'type' => 'required|string'
        ]);

        $seo = $this->service->removeMetaTag(
            $uuid,
            $request->get('group'),
            $request->get('type')
        );

        return response()->json([
            'data' => $this->toResource($seo),
            'message' => 'Meta tag uğurla silindi'
        ]);
    }

    /**
     * SEO analizi aparır.
     * Admin panel tərəfindən istifadə olunur.
     */
    public function analyze(string $uuid): JsonResponse
    {
        $analysis = $this->service->analyzeSeo($uuid);

        return response()->json([
            'data' => $analysis,
            'message' => 'SEO analizi uğurla tamamlandı'
        ]);
    }

    /**
     * Preview məlumatlarını generasiya edir.
     * Admin panel tərəfindən istifadə olunur.
     */
    public function preview(string $uuid): JsonResponse
    {
        $previews = $this->service->generatePreviews($uuid);

        return response()->json([
            'data' => $previews,
            'message' => 'Preview məlumatları uğurla generasiya edildi'
        ]);
    }

    /**
     * Meta tag təklifləri generasiya edir.
     * Admin panel tərəfindən istifadə olunur.
     */
    public function suggestions(Request $request): JsonResponse
    {
        $suggestions = $this->service->generateSuggestions($request->get('uuid'));

        return response()->json([
            'data' => $suggestions,
            'message' => 'Təkliflər uğurla generasiya edildi'
        ]);
    }

    /**
     * SEO dəyişiklik tarixçəsini qaytarır.
     * Admin panel tərəfindən istifadə olunur.
     */
    public function history(string $uuid): JsonResponse
    {
        $history = $this->service->getHistory($uuid);

        return response()->json([
            'data' => $history,
            'message' => 'Tarixçə uğurla əldə edildi'
        ]);
    }

    /**
     * API-nin validasiya qaydaları
     */
    public function commonRules(): array
    {
        return [
            'url' => 'required|string',
            'type' => 'nullable|string|in:' . implode(',', SeoTypeEnum::getValues()),
            'basic_meta' => 'array',
            'basic_meta.title' => 'required|string|max:60',
            'basic_meta.description' => 'required|string|max:160',
            'basic_meta.keywords' => 'nullable|string',

            'open_graph' => 'array',
            'open_graph.og:title' => 'nullable|string|max:95',
            'open_graph.og:description' => 'nullable|string|max:200',
            'open_graph.og:image' => 'nullable|url',

            'twitter' => 'array',
            'twitter.twitter:title' => 'nullable|string|max:70',
            'twitter.twitter:description' => 'nullable|string|max:200',
            'twitter.twitter:image' => 'nullable|url',

            'technical' => 'array',
            'technical.canonical' => 'nullable|url',
            'technical.robots' => 'nullable|string',

            'is_sitemap' => 'boolean',
            'sitemap_priority' => 'nullable|numeric|min:0.1|max:1.0',
            'sitemap_frequency' => 'nullable|in:always,hourly,daily,weekly,monthly,yearly,never'
        ];
    }

    /**
     * API-nin xəta mesajları
     */
    protected function commonMessages(): array
    {
        return [
            'url.required' => 'URL məcburidir',
            'basic_meta.title.required' => 'Title məcburidir',
            'basic_meta.title.max' => 'Title maksimum 60 simvol ola bilər',
            'basic_meta.description.required' => 'Description məcburidir',
            'basic_meta.description.max' => 'Description maksimum 160 simvol ola bilər',
            'open_graph.og:title.max' => 'OpenGraph title maksimum 95 simvol ola bilər',
            'open_graph.og:description.max' => 'OpenGraph description maksimum 200 simvol ola bilər',
            'twitter.twitter:title.max' => 'Twitter title maksimum 70 simvol ola bilər',
            'twitter.twitter:description.max' => 'Twitter description maksimum 200 simvol ola bilər'
        ];
    }

    public function typeOptions(Request $request): JsonResponse
    {
        return response()->json(ReferenceResource::collection($this->service->getTypeOptions($request->type)));
    }
}
