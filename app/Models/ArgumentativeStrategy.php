<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArgumentativeStrategy extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'analysis_id',
        'strategy_name',
        'who_uses',
        'excerpt',
        'explanation',
        'severity',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function analysis(): BelongsTo
    {
        return $this->belongsTo(Analysis::class);
    }
}
