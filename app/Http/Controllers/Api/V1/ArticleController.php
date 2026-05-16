<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ListArticlesRequest;
use App\Http\Resources\AnalysisResource;
use App\Http\Resources\ArticleResource;
use App\Models\Article;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ArticleController extends Controller
{
    public function index(ListArticlesRequest $request): AnonymousResourceCollection
    {
        $query = Article::query()
            ->with('source')
            ->withExists('analysis')
            ->orderByDesc('published_at')
            ->orderByDesc('id');

        if ($request->filled('source_id')) {
            $query->where('source_id', $request->integer('source_id'));
        }

        if ($request->has('has_analysis')) {
            $request->boolean('has_analysis')
                ? $query->whereHas('analysis')
                : $query->whereDoesntHave('analysis');
        }

        if ($request->filled('published_from')) {
            $query->whereDate('published_at', '>=', $request->date('published_from'));
        }

        if ($request->filled('published_to')) {
            $query->whereDate('published_at', '<=', $request->date('published_to'));
        }

        if ($request->filled('q')) {
            $query->where('title', 'like', '%'.(string) $request->string('q').'%');
        }

        return ArticleResource::collection(
            $query->paginate($this->perPage($request->validated()))
        );
    }

    public function show(Article $article): ArticleResource
    {
        return new ArticleResource(
            $article->load(['source', 'analysis.strategies', 'events'])
        );
    }

    public function analysis(Article $article): AnalysisResource
    {
        $article->load('analysis.strategies');

        if ($article->analysis === null) {
            abort(404, 'Analise ainda nao disponivel para este artigo.');
        }

        return new AnalysisResource($article->analysis);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function perPage(array $validated): int
    {
        return (int) ($validated['per_page'] ?? 15);
    }
}
