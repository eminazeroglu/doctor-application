<?php

namespace App\Http\Controllers\Api\Front;

use App\Http\Controllers\Controller;
use App\Repositories\Module\UserRepository;
use App\Traits\Controller\HasValidatesRequests;

class UserController extends Controller
{
    use HasValidatesRequests;
    public UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }
}
