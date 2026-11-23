<?php

namespace App\Http\Controllers\Api\V1;

use App\Filters\UserFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\User\SearchUsersRequest;
use App\Http\Resources\V1\UserResource;
use App\Http\Resources\V1\UserSummaryResource;
use App\Interfaces\Repositories\UserRepositoryInterface;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * Directory of active users, available to every signed-in user.
 * Managing users (create, roles, deactivate) lives in Admin\UserController.
 */
class UserController extends Controller
{
    public function __construct(private readonly UserRepositoryInterface $users) {}

    public function index(SearchUsersRequest $request): JsonResponse
    {
        $filter = new UserFilter([...$request->validated(), 'is_active' => true]);

        $users = $this->users->paginate($filter, $request->integer('per_page', 15));

        return ApiResponse::success(UserSummaryResource::collection($users));
    }

    public function show(User $user): JsonResponse
    {
        abort_unless($user->is_active, 404);

        return ApiResponse::success(new UserResource($user->load('roles')));
    }
}
