<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\EventResource;
use App\Http\Resources\SourceComparisonResource;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EventController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'category' => ['sometimes', 'string', 'max:100'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Event::query()
            ->withCount('articles')
            ->orderByDesc('detected_at')
            ->orderByDesc('id');

        if ($request->filled('category')) {
            $query->where('category', (string) $request->string('category'));
        }

        return EventResource::collection(
            $query->paginate((int) ($validated['per_page'] ?? 15))
        );
    }

    public function show(Event $event): EventResource
    {
        return new EventResource(
            $event->load(['articles.source', 'comparison'])
        );
    }

    public function comparison(Event $event): SourceComparisonResource
    {
        $event->load('comparison');

        if ($event->comparison === null) {
            abort(404, 'Comparacao ainda nao disponivel para este evento.');
        }

        return new SourceComparisonResource($event->comparison);
    }
}
