<?php

namespace App\Http\Controllers\Api\V1\Admin\User;

use App\Actions\Admin\CreateUserAction;
use App\Actions\Admin\DeleteUserAction;
use App\Actions\Admin\UpdateUserAction;
use App\DTOs\Admin\CreateUserData;
use App\DTOs\Admin\UpdateUserData;
use App\Filters\UserFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\User\IndexUsersRequest;
use App\Http\Requests\V1\Admin\User\StoreUserRequest;
use App\Http\Requests\V1\Admin\User\UpdateUserRequest;
use App\Http\Resources\V1\UserResource;
use App\Interfaces\Repositories\UserRepositoryInterface;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Admin > Users (CRUD). Role and status changes have their own invokable controllers.
 * Every endpoint is protected by UserPolicy (users.manage).
 */
class UserController extends Controller
{
    public function __construct(private readonly UserRepositoryInterface $users) {}

    public function index(IndexUsersRequest $request): JsonResponse
    {
        $users = $this->users->paginate(
            new UserFilter($request->validated()),
            $request->integer('per_page', 15),
            with: ['roles'],
        );

        return ApiResponse::success(UserResource::collection($users));
    }

    public function store(StoreUserRequest $request, CreateUserAction $action): JsonResponse
    {
        $user = $action->handle(CreateUserData::fromRequest($request));

        return ApiResponse::created(new UserResource($user->load('roles')), __('User created successfully.'));
    }

    public function show(User $user): JsonResponse
    {
        Gate::authorize('update', $user);

        return ApiResponse::success(new UserResource($user->load('roles')));
    }

    public function update(UpdateUserRequest $request, User $user, UpdateUserAction $action): JsonResponse
    {
        $user = $action->handle($user, UpdateUserData::fromRequest($request));

        return ApiResponse::success(new UserResource($user->load('roles')), __('User updated successfully.'));
    }

    public function destroy(User $user, #[CurrentUser] User $actor, DeleteUserAction $action): JsonResponse
    {
        Gate::authorize('delete', $user);

        $action->handle($actor, $user);

        return ApiResponse::message(__('User deleted successfully.'));
    }
}
