<?php

namespace Database\Factories;

use App\Enums\InterviewCategory;
use App\Enums\InterviewDuration;
use App\Enums\InterviewLevel;
use App\Enums\InterviewStatus;
use App\Enums\UsageReason;
use App\Models\InterviewSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InterviewSession>
 */
class InterviewSessionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'category' => fake()->randomElement(InterviewCategory::cases()),
            'level' => fake()->randomElement(InterviewLevel::cases()),
            'duration_minutes' => fake()->randomElement(InterviewDuration::cases()),
            'status' => InterviewStatus::Draft,
        ];
    }

    /**
     * A started session consumed one unit of the allowance when it started,
     * just like InterviewFlow::start() records it.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (InterviewSession $session) {
            if ($session->started_at !== null) {
                $session->user->usageRecords()->create([
                    'interview_session_id' => $session->id,
                    'reason' => UsageReason::InterviewStarted,
                    'created_at' => $session->started_at,
                ]);
            }
        });
    }

    /**
     * Indicate that the microphone and audio checks are done.
     */
    public function ready(): static
    {
        return $this->state(fn () => ['status' => InterviewStatus::Ready]);
    }

    /**
     * Indicate that the interview has started and is waiting for answers.
     */
    public function inProgress(): static
    {
        return $this->state(fn () => [
            'status' => InterviewStatus::Asking,
            'started_at' => now(),
        ]);
    }

    /**
     * Indicate that the interview has finished and its report is available.
     */
    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => InterviewStatus::ReportReady,
            'started_at' => now()->subMinutes(20),
            'completed_at' => now(),
        ]);
    }
}
