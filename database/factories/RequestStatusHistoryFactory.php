<?php

namespace Database\Factories;

use App\Models\RequestStatusHistory;
use App\Models\ServiceRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RequestStatusHistory>
 */
class RequestStatusHistoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'request_id' => ServiceRequest::factory(),
            'from_status' => null,
            'to_status' => ServiceRequest::STATUS_SENT,
            'changed_by' => null,
            'note' => null,
        ];
    }
}
