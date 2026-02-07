<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;

class UserApiController extends Controller
{
    /**
     * List all users.
     */
    public function index(): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        return UserResource::collection(User::all());
    }
}
