<?php

use App\Http\Controllers\Api\V1\AnalysisController;
use App\Http\Controllers\Api\V1\ArticleController;
use App\Http\Controllers\Api\V1\EventController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\SourceController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    Route::get('health', HealthController::class)->name('health');

    Route::get('sources', [SourceController::class, 'index'])->name('sources.index');
    Route::get('sources/{source}', [SourceController::class, 'show'])->name('sources.show');
    Route::get('sources/{source}/articles', [SourceController::class, 'articles'])->name('sources.articles.index');

    Route::get('articles', [ArticleController::class, 'index'])->name('articles.index');
    Route::get('articles/{article}', [ArticleController::class, 'show'])->name('articles.show');
    Route::get('articles/{article}/analysis', [ArticleController::class, 'analysis'])->name('articles.analysis.show');

    Route::get('analyses', [AnalysisController::class, 'index'])->name('analyses.index');
    Route::get('analyses/{analysis}', [AnalysisController::class, 'show'])->name('analyses.show');

    Route::get('events', [EventController::class, 'index'])->name('events.index');
    Route::get('events/{event}', [EventController::class, 'show'])->name('events.show');
    Route::get('events/{event}/comparison', [EventController::class, 'comparison'])->name('events.comparison.show');
});
