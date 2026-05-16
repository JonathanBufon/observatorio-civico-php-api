<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnalysisResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'article_id' => $this->article_id,
            'rewritten_text' => $this->rewritten_text,
            'fact_fragments' => $this->fact_fragments,
            'opinion_fragments' => $this->opinion_fragments,
            'simplified_terms' => $this->simplified_terms,
            'bias_indicators' => $this->bias_indicators,
            'transparency_log' => $this->transparency_log,
            'model_used' => $this->model_used,
            'prompt_version' => $this->prompt_version,
            'processed_at' => $this->processed_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'article' => new ArticleResource($this->whenLoaded('article')),
            'strategies' => ArgumentativeStrategyResource::collection($this->whenLoaded('strategies')),
        ];
    }
}
