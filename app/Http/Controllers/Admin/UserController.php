<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        protected UserService $userService
    ) {}

    public function index()
    {
        $this->authorize('viewAny', User::class);
        $users = $this->userService->getAllUsers();
        return $this->successResponse(UserResource::collection($users));
    }

    public function store(StoreUserRequest $request)
    {
        $this->authorize('create', User::class);
        $user = $this->userService->createUser($request->validated());
        return $this->successResponse(new UserResource($user), 201);
    }

    public function show($id)
    {
        try {
            $user = $this->userService->getUserById($id);
            $this->authorize('view', $user);
            return $this->successResponse(new UserResource($user));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('User not found.', 404);
        }
    }

    public function update(UpdateUserRequest $request, $id)
    {
        try {
            $user = User::findOrFail($id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('User not found.', 404);
        }

        $this->authorize('update', $user);
        $updatedUser = $this->userService->updateUser($user, $request->validated());
        return $this->successResponse(new UserResource($updatedUser));
    }

    public function destroy($id)
    {
        try {
            $user = User::findOrFail($id);
            $this->authorize('delete', $user);
            $this->userService->deleteUser($user);
            return $this->successResponse(null, 204);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('User not found.', 404);
        }
    }
}
