<?php

namespace Tests\Unit\Models;

use App\Models\ServiceRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ServiceRequestTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: string, 1: bool}>
     */
    public static function statusProvider(): array
    {
        return [
            'draft is not final' => [ServiceRequest::STATUS_DRAFT, false],
            'sent is not final' => [ServiceRequest::STATUS_SENT, false],
            'viewed is not final' => [ServiceRequest::STATUS_VIEWED, false],
            'accepted is not final' => [ServiceRequest::STATUS_ACCEPTED, false],
            'in_progress is not final' => [ServiceRequest::STATUS_IN_PROGRESS, false],
            'completed is final' => [ServiceRequest::STATUS_COMPLETED, true],
            'refused is final' => [ServiceRequest::STATUS_REFUSED, true],
            'cancelled is final' => [ServiceRequest::STATUS_CANCELLED, true],
        ];
    }

    #[DataProvider('statusProvider')]
    public function test_is_final_matches_expected_statuses(string $status, bool $expected): void
    {
        $request = ServiceRequest::factory()->create(['status' => $status]);

        $this->assertSame($expected, $request->isFinal());
    }
}
