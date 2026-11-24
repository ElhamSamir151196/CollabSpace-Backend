<?php

namespace App\Http\Controllers\Api\V1\Search;

use App\Http\Controllers\Controller;
use App\Models\SearchHistory;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

/**
 * DELETE /search/history — clears all recent searches.
 */
class ClearSearchHistoryController extends Controller
{
    public function __invoke(#[CurrentUser] User $user): JsonResponse
    {
        SearchHistory::query()->where('user_id', $user->id)->delete();

        return ApiResponse::message(__('Recent searches cleared.'));
    }
}
