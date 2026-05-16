<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ListAnalysesRequest;
use App\Http\Resources\AnalysisResource;
use App\Models\Analysis;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AnalysisController extends Controller
{
    public function index(ListAnalysesRequest $request): AnonymousResourceCollection
    {
        $query = Analysis::query()
            ->with(['article.source', 'strategies'])
            ->orderByDesc('processed_at')
            ->orderByDesc('id');

        if ($request->filled('source_id')) {
            $query->whereHas('article', function ($articleQuery) use ($request): void {
                $articleQuery->where('source_id', $request->integer('source_id'));
            });
        }

        if ($request->filled('model_used')) {
            $query->where('model_used', (string) $request->string('model_used'));
        }

        if ($request->filled('prompt_version')) {
            $query->where('prompt_version', (string) $request->string('prompt_version'));
        }

        return AnalysisResource::collection(
            $query->paginate($this->perPage($request->validated()))
        );
    }

    public function show(Analysis $analysis): AnalysisResource
    {
        return new AnalysisResource(
            $analysis->load(['article.source', 'strategies'])
        );
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function perPage(array $validated): int
    {
        return (int) ($validated['per_page'] ?? 15);
    }
}
