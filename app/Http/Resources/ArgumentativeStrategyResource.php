<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArgumentativeStrategyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'analysis_id' => $this->analysis_id,
            'strategy_name' => $this->strategy_name,
            'who_uses' => $this->who_uses,
            'excerpt' => $this->excerpt,
            'explanation' => $this->explanation,
            'severity' => $this->severity,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
