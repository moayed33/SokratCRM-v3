<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CalendarEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CalendarEvent>
 */
class CalendarEventFactory extends Factory
{
    protected $model = CalendarEvent::class;

    public function definition(): array
    {
        $startTime = fake()->dateTimeBetween('now', '+1 month');
        $endTime = (clone $startTime)->modify('+1 hour');

        return [
            'user_id' => User::factory(),
            'lead_id' => null,
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'type' => fake()->randomElement(['meeting', 'call', 'task', 'reminder']),
            'status' => fake()->randomElement(['scheduled', 'completed', 'canceled']),
            'reminder_minutes_before' => 15,
        ];
    }

    public function meeting(): static
    {
        return $this->state(fn (array $attributes) => ['type' => 'meeting']);
    }

    public function call(): static
    {
        return $this->state(fn (array $attributes) => ['type' => 'call']);
    }

    public function task(): static
    {
        return $this->state(fn (array $attributes) => ['type' => 'task']);
    }

    public function reminder(): static
    {
        return $this->state(fn (array $attributes) => ['type' => 'reminder']);
    }

    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'scheduled']);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'completed']);
    }

    public function canceled(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'canceled']);
    }

    public function forUser(mixed $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user instanceof User ? $user->id : $user,
        ]);
    }

    public function forLead(mixed $lead): static
    {
        return $this->state(fn (array $attributes) => [
            'lead_id' => $lead instanceof \App\Models\Lead ? $lead->id : $lead,
        ]);
    }
}
