<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['request_id', 'from_status', 'to_status', 'changed_by', 'note'])]
class RequestStatusHistory extends Model
{
    /** @use HasFactory<\Database\Factories\RequestStatusHistoryFactory> */
    use HasFactory;

    protected $table = 'request_status_history';

    public const UPDATED_AT = null;

    public function request(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class, 'request_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
