<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuditLogService
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function log(?User $user, string $action, ?Model $auditable = null, array $metadata = []): AuditLog
    {
        return AuditLog::create([
            'user_id' => $user?->id,
            'action' => $action,
            'auditable_type' => $auditable?->getMorphClass(),
            'auditable_id' => $auditable?->getKey(),
            'metadata' => $metadata,
            'ip_address' => request()?->ip(),
        ]);
    }
}
