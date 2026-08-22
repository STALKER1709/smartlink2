<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'subscription_id' => Subscription::factory(),
            'payer_id' => User::factory()->provider(),
            'amount_xaf' => 2500,
            'operator' => 'mtn',
            'phone' => '677123456',
            'status' => Payment::STATUS_PENDING,
            'internal_reference' => 'SL-'.strtoupper(Str::random(12)),
        ];
    }

    public function successful(): static
    {
        return $this->state(fn () => [
            'status' => Payment::STATUS_SUCCESS,
            'paid_at' => now(),
        ]);
    }
}
