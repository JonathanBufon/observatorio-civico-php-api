<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArticleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'source_id' => $this->source_id,
            'external_id' => $this->external_id,
            'title' => $this->title,
            'original_url' => $this->original_url,
            'content' => $this->content,
            'has_analysis' => $this->when(
                array_key_exists('analysis_exists', $this->resource->getAttributes()),
                (bool) $this->analysis_exists
            ),
            'published_at' => $this->published_at?->toISOString(),
            'fetched_at' => $this->fetched_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'source' => new SourceResource($this->whenLoaded('source')),
            'analysis' => new AnalysisResource($this->whenLoaded('analysis')),
            'events' => EventResource::collection($this->whenLoaded('events')),
        ];
    }
}
