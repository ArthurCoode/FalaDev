<?php

namespace App\Jobs;

use App\Enums\InterviewStatus;
use App\Enums\PilotEventType;
use App\Evaluation\Contracts\Evaluator;
use App\Models\InterviewSession;
use App\Models\PilotEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Produces the report after the interview, in the background
 * (cursor/skills/FalaDev.md, sections 3 and 5C).
 *
 * Any failure keeps the interview and its transcript, and moves the session
 * to failed_retryable so the page can offer a new attempt. A partial report
 * is never saved.
 */
class EvaluateInterview implements ShouldQueue
{
    use Queueable;

    /** The session may be deleted before the job runs. */
    public bool $deleteWhenMissingModels = true;

    public function __construct(public InterviewSession $session) {}

    public function handle(Evaluator $evaluator): void
    {
        $session = $this->session->fresh();

        if ($session?->status !== InterviewStatus::Evaluating) {
            return;
        }

        $startedAt = microtime(true);

        try {
            $result = $evaluator->evaluate($session);
        } catch (Throwable $e) {
            report($e);
            PilotEvent::record(PilotEventType::EvaluationFailed, $session->user_id, $session->id);
            $this->markFailed($session);

            return;
        }

        DB::transaction(function () use ($session, $result, $evaluator, $startedAt) {
            $session->evaluation()->updateOrCreate([], [
                'evaluator' => $evaluator->name(),
                'overall_score' => $result->overallScore,
                'summary' => $result->summary,
                'dimensions' => $result->dimensions,
                'strengths' => $result->strengths,
                'improvements' => $result->improvements,
                'english_feedback' => $result->englishFeedback,
                'next_steps' => $result->nextSteps,
                'metadata' => [
                    ...$result->metadata,
                    'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                ],
            ]);

            $session->transitionTo(InterviewStatus::ReportReady);
            $session->save();
        });
    }

    private function markFailed(InterviewSession $session): void
    {
        $session->transitionTo(InterviewStatus::FailedRetryable);
        $session->save();
    }
}
