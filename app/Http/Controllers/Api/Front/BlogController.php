<?php

namespace App\Http\Controllers\Api\Front;

use App\Http\Controllers\Controller;
use App\Http\Resources\Front\BlogResource;
use App\Http\Resources\Front\BlogViewResource;
use App\Http\Resources\Front\DoctorResource;
use App\Services\Module\BlogService;
use App\Services\Module\DoctorService;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    public BlogService $blogService;

    public function __construct(BlogService $blogService)
    {
        $this->blogService = $blogService;
    }

    public function blogSearch()
    {
        $blogs = $this->blogService->blogSearch();
        return response()->json([
            'items' => BlogResource::collection($blogs),
            'total' => $blogs->total()
        ]);
    }

    public function blogView($slug)
    {
        $blog = $this->blogService->blogView($slug);
        $doctors = app(DoctorService::class)->getDoctorsByCategory($blog->category_id, 6);
        $lastBlogs = $this->blogService->blogLast($blog->id);

        $response = [
            'blog' => new BlogViewResource($blog),
            'last_blogs' => BlogResource::collection($lastBlogs),
            'doctors' => DoctorResource::collection($doctors)
        ];

        if (count($doctors) === 0) {
            $response['is_random'] = true;
            $response['doctors'] = DoctorResource::collection(app(DoctorService::class)->getDoctorsRandom());
        }

        return response()->json($response);
    }
}
