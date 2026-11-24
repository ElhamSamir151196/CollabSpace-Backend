<?php

namespace App\Http\Controllers\Api\V1\Search;

use App\Http\Controllers\Controller;
use App\Models\SearchHistory;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

/**
 * "Recent Searches": GET /search/history, DELETE /search/history/{history}.
 */
class SearchHistoryController extends Controller
{
    public function index(#[CurrentUser] User $user): JsonResponse
    {
        $items = $user->searchHistories()->get()->map(fn (SearchHistory $item) => [
            'id' => $item->id,
            'query' => $item->query,
            'searched_at' => $item->updated_at?->toIso8601String(),
        ]);

        return ApiResponse::success($items);
    }

    public function destroy(int $history, #[CurrentUser] User $user): JsonResponse
    {
        $user->searchHistories()->findOrFail($history)->delete();

        return ApiResponse::message(__('Removed from recent searches.'));
    }
}
