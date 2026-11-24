<?php

namespace App\Http\Controllers\Api\V1\Search;

use App\Actions\Search\RecordSearchAction;
use App\DTOs\Search\SearchResult;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Search\GlobalSearchRequest;
use App\Models\User;
use App\Services\Search\GlobalSearch;
use App\Support\ApiResponse;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

/**
 * GET /search?q= — results grouped by section, only what the user may see.
 * The query is saved in "Recent Searches" unless save=0
 * (send save=0 while the user is still typing, save=1 when they submit).
 */
class GlobalSearchController extends Controller
{
    public function __invoke(
        GlobalSearchRequest $request,
        #[CurrentUser] User $user,
        GlobalSearch $search,
        RecordSearchAction $record,
    ): JsonResponse {
        $term = $request->string('q')->trim()->toString();

        $sections = $search->search($user, $term, $request->types(), $request->integer('limit', 5));

        if ($request->boolean('save', true)) {
            $record->handle($user, $term);
        }

        $data = array_map(
            fn (array $results) => array_map(fn (SearchResult $result) => $result->toArray(), $results),
            $sections,
        );

        return ApiResponse::success($data, meta: [
            'query' => $term,
            'total' => array_sum(array_map('count', $sections)),
        ]);
    }
}
