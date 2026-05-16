<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SourceComparisonResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event_id' => $this->event_id,
            'common_facts' => $this->common_facts,
            'divergent_frames' => $this->divergent_frames,
            'missing_aspects' => $this->missing_aspects,
            'synthesis' => $this->synthesis,
            'model_used' => $this->model_used,
            'prompt_version' => $this->prompt_version,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
