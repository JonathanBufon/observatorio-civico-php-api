<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SourceComparison extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'event_id',
        'common_facts',
        'divergent_frames',
        'missing_aspects',
        'synthesis',
        'model_used',
        'prompt_version',
    ];

    protected function casts(): array
    {
        return [
            'common_facts' => 'array',
            'divergent_frames' => 'array',
            'missing_aspects' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
