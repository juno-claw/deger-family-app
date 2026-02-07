<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserApiController extends Controller
{
    /**
     * List all users (excludes sensitive fields via UserResource).
     */
    public function index(): AnonymousResourceCollection
    {
        return UserResource::collection(
            User::select(['id', 'name', 'email', 'role'])->get()
        );
    }
}
