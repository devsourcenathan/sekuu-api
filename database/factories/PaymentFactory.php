<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        $amount = $this->faker->randomFloat(2, 10, 500);
        $platformFee = $amount * 0.15;
        
        return [
            'user_id' => User::factory(),
            'course_id' => Course::factory(),
            'payment_gateway' => $this->faker->randomElement(['stripe', 'paypal', 'bank_transfer']),
            'gateway_transaction_id' => 'GTX-' . $this->faker->uuid(),
            'amount' => $amount,
            'currency' => 'USD',
            'platform_fee' => $platformFee,
            'instructor_amount' => $amount - $platformFee,
            'status' => $this->faker->randomElement(['pending', 'completed', 'failed', 'refunded']),
            'discount_amount' => 0,
            'metadata' => [],
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
        ]);
    }
}
