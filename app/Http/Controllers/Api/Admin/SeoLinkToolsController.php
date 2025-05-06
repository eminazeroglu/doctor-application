<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\Module\SeoLinkToolsService;
use Illuminate\Http\Response;

class SeoLinkToolsController extends Controller
{
    protected SeoLinkToolsService $toolsService;

    public function __construct(SeoLinkToolsService $toolsService)
    {
        $this->toolsService = $toolsService;
    }

    /**
     * Sitemap.xml faylını qaytarır
     */
    public function sitemap(): Response
    {
        $content = $this->toolsService->generateSitemap();

        return response($content)
            ->header('Content-Type', 'application/xml');
    }

    /**
     * Robots.txt faylını qaytarır
     */
    public function robots(): Response
    {
        $content = $this->toolsService->generateRobots();

        return response($content)
            ->header('Content-Type', 'text/plain');
    }

    /**
     * Sitemap index faylını qaytarır
     */
    public function sitemapIndex(): Response
    {
        $content = $this->toolsService->generateSitemapIndex();

        return response($content)
            ->header('Content-Type', 'application/xml');
    }
}
