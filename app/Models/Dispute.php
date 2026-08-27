<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['request_id', 'reporter_id', 'reason', 'description', 'evidence_paths'])]
class Dispute extends Model
{
    use SoftDeletes;

    public const STATUS_OPEN = 'open';

    public const STATUS_RESOLVED = 'resolved';

    public const STATUS_REJECTED = 'rejected';

    /**
     * Les motifs proposés au signalement. La liste est fermée : « Autre » y
     * figure pour que personne ne renonce faute de case, et c'est la
     * description qui porte alors le détail.
     *
     * @return array<string, string>
     */
    public static function reasons(): array
    {
        return [
            'service_non_conforme' => 'Service non conforme à la description',
            'comportement_inapproprie' => 'Comportement inapproprié',
            'retard_important' => 'Retard important ou absence',
            'dommages' => 'Dommages matériels constatés',
            'autre' => 'Autre motif',
        ];
    }

    public static function reasonLabel(?string $reason): string
    {
        return self::reasons()[$reason] ?? ($reason ?? '—');
    }

    /**
     * @return array<string, string>
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_OPEN => 'Ouvert',
            self::STATUS_RESOLVED => 'Résolu',
            self::STATUS_REJECTED => 'Rejeté',
        ];
    }

    protected function casts(): array
    {
        return [
            'evidence_paths' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class, 'request_id');
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_OPEN);
    }
}
