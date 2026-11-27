<?php

namespace Database\Factories;

use App\Models\InterviewSession;
use App\Models\InterviewTurn;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InterviewTurn>
 */
class InterviewTurnFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'interview_session_id' => InterviewSession::factory(),
            'position' => 1,
            'question' => fake()->sentence().'?',
            'is_follow_up' => false,
        ];
    }

    /**
     * Indicate that the user has confirmed an answer for this turn.
     */
    public function answered(): static
    {
        return $this->state(fn () => [
            'answer_transcript' => fake()->paragraph(),
            'answered_at' => now(),
        ]);
    }
}
