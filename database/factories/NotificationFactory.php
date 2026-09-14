<?php

namespace Database\Factories;

use App\Models\Notification;
use App\Models\ProcurementRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Notification>
 */
class NotificationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'procurement_request_id' => ProcurementRequest::factory(),
            'channel' => Notification::CHANNEL_WHATSAPP,
            'recipient' => '6281234567890',
            'message_content' => $this->faker->sentence(),
            'status' => Notification::STATUS_PENDING,
            'error_message' => null,
            'sent_at' => null,
        ];
    }

    public function whatsapp(): static
    {
        return $this->state(fn () => ['channel' => Notification::CHANNEL_WHATSAPP]);
    }

    public function email(): static
    {
        return $this->state(fn () => ['channel' => Notification::CHANNEL_EMAIL]);
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => Notification::STATUS_PENDING]);
    }

    public function sent(): static
    {
        return $this->state(fn () => [
            'status' => Notification::STATUS_SENT,
            'sent_at' => now(),
        ]);
    }

    public function failed(string $error = 'Test error'): static
    {
        return $this->state(fn () => [
            'status' => Notification::STATUS_FAILED,
            'error_message' => $error,
        ]);
    }
}
