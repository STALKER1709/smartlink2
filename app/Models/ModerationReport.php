<?php

namespace App\Models;

use Database\Factories\ModerationReportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Verdict de la modération automatique sur un contenu publié. L'IA classe et
 * signale ; elle ne supprime jamais rien. Un administrateur tranche.
 */
#[Fillable([
    'moderatable_type', 'moderatable_id', 'verdict', 'categories',
    'reason', 'model', 'reviewed_by', 'reviewed_at',
])]
class ModerationReport extends Model
{
    /** @use HasFactory<ModerationReportFactory> */
    use HasFactory;

    public const VERDICT_CLEAN = 'clean';

    public const VERDICT_FLAGGED = 'flagged';

    protected function casts(): array
    {
        return [
            'categories' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    public function moderatable(): MorphTo
    {
        return $this->morphTo();
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isFlagged(): bool
    {
        return $this->verdict === self::VERDICT_FLAGGED;
    }

    /** Signalements qui attendent encore la décision d'un administrateur. */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('verdict', self::VERDICT_FLAGGED)->whereNull('reviewed_at');
    }
}
