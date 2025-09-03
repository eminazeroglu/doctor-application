<?php

namespace App\Http\Controllers\Api\Front;

use App\Http\Controllers\Controller;
use App\Http\Resources\Front\DoctorResource;
use App\Http\Resources\Front\DoctorViewResource;
use App\Repositories\Module\DoctorRepository;
use Illuminate\Http\JsonResponse;

class DoctorController extends Controller
{
    public DoctorRepository $doctorRepository;

    public function __construct(DoctorRepository $doctorRepository)
    {
        $this->doctorRepository = $doctorRepository;
    }

    public function doctorSearch(): JsonResponse
    {
        return response()->json(DoctorResource::collection($this->doctorRepository->doctorSearch(request())));
    }

    public function doctorView($id): JsonResponse
    {
        return response()->json(new DoctorViewResource($this->doctorRepository->doctorView($id)));
    }
}
