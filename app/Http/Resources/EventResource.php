<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'summary' => $this->summary,
            'category' => $this->category,
            'articles_count' => $this->whenCounted('articles'),
            'detected_at' => $this->detected_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'articles' => ArticleResource::collection($this->whenLoaded('articles')),
            'comparison' => new SourceComparisonResource($this->whenLoaded('comparison')),
        ];
    }
}
