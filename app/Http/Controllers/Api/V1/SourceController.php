<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ListArticlesRequest;
use App\Http\Requests\Api\V1\ListSourcesRequest;
use App\Http\Resources\ArticleResource;
use App\Http\Resources\SourceResource;
use App\Models\Source;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SourceController extends Controller
{
    public function index(ListSourcesRequest $request): AnonymousResourceCollection
    {
        $query = Source::query()
            ->withCount('articles')
            ->orderBy('name');

        if ($request->has('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        if ($request->filled('editorial_leaning')) {
            $query->where('editorial_leaning', (string) $request->string('editorial_leaning'));
        }

        return SourceResource::collection(
            $query->paginate($this->perPage($request->validated()))
        );
    }

    public function show(Source $source): SourceResource
    {
        return new SourceResource($source->loadCount('articles'));
    }

    public function articles(Source $source, ListArticlesRequest $request): AnonymousResourceCollection
    {
        $query = $source->articles()
            ->with('source')
            ->withExists('analysis')
            ->orderByDesc('published_at')
            ->orderByDesc('id');

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

    /**
     * @param  array<string, mixed>  $validated
     */
    private function perPage(array $validated): int
    {
        return (int) ($validated['per_page'] ?? 15);
    }
}
