<?php

namespace App\Http\Controllers\Api\Front;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\DoctorResource;
use App\Repositories\Module\DoctorRepository;

class DoctorController extends Controller
{
    public $doctorRepository;

    public function __construct(DoctorRepository $doctorRepository)
    {
        $this->doctorRepository = $doctorRepository;
    }

    public function doctorSearch()
    {
        return response()->json(DoctorResource::collection($this->doctorRepository->doctorSearch(request())));
    }
}
