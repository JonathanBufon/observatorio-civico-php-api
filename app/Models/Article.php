<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Article extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'source_id',
        'external_id',
        'title',
        'original_url',
        'content',
        'published_at',
        'fetched_at',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'fetched_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }

    public function analysis(): HasOne
    {
        return $this->hasOne(Analysis::class);
    }

    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class, 'article_events')
            ->withPivot('similarity_score');
    }
}
