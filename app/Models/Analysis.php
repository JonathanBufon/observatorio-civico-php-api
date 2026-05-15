<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Analysis extends Model
{
    public $timestamps = false;

    protected $table = 'analyses';

    protected $fillable = [
        'article_id',
        'rewritten_text',
        'fact_fragments',
        'opinion_fragments',
        'simplified_terms',
        'bias_indicators',
        'transparency_log',
        'model_used',
        'prompt_version',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'fact_fragments' => 'array',
            'opinion_fragments' => 'array',
            'simplified_terms' => 'array',
            'bias_indicators' => 'array',
            'processed_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function strategies(): HasMany
    {
        return $this->hasMany(ArgumentativeStrategy::class);
    }
}
