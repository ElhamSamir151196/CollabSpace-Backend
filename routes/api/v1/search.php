<?php

use App\Http\Controllers\Api\V1\Search\ClearSearchHistoryController;
use App\Http\Controllers\Api\V1\Search\GlobalSearchController;
use App\Http\Controllers\Api\V1\Search\SearchHistoryController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Global search
|--------------------------------------------------------------------------
|
| /api/v1/search?q=                  Name:  v1.search
| /api/v1/search/history/{history}   Names: v1.search.history.*
|
*/

Route::middleware(['auth:sanctum', 'active'])->group(function () {
    Route::get('search', GlobalSearchController::class)->name('search');

    Route::get('search/history', [SearchHistoryController::class, 'index'])->name('search.history.index');
    Route::delete('search/history', ClearSearchHistoryController::class)->name('search.history.clear');
    Route::delete('search/history/{history}', [SearchHistoryController::class, 'destroy'])
        ->whereNumber('history')
        ->name('search.history.destroy');
});
