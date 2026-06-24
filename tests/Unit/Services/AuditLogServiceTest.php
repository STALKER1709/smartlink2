<?php

namespace Tests\Unit\Services;

use App\Models\Service;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_log_records_user_action_and_auditable_subject(): void
    {
        $admin = User::factory()->admin()->create();
        $service = Service::factory()->create();

        $log = app(AuditLogService::class)->log($admin, 'service.status_toggled', $service, ['status' => 'inactive']);

        $this->assertSame($admin->id, $log->user_id);
        $this->assertSame('service.status_toggled', $log->action);
        $this->assertSame($service->getMorphClass(), $log->auditable_type);
        $this->assertSame($service->id, $log->auditable_id);
        $this->assertSame(['status' => 'inactive'], $log->metadata);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'service.status_toggled',
        ]);
    }

    public function test_log_allows_null_user_and_null_auditable(): void
    {
        $log = app(AuditLogService::class)->log(null, 'system.event');

        $this->assertNull($log->user_id);
        $this->assertNull($log->auditable_type);
        $this->assertNull($log->auditable_id);
        $this->assertSame('system.event', $log->action);
    }
}
