<?php

namespace App\Interview;

use App\Enums\InterviewStatus;
use App\Interview\Contracts\Interviewer;
use App\Jobs\EvaluateInterview;
use App\Models\InterviewSession;
use App\Models\InterviewTurn;
use App\Services\InterviewUsage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Turn-by-turn interview rules (cursor/skills/FalaDev.md, sections 5B and 8).
 *
 * Every write locks the session row, so double clicks and retries after a
 * dropped connection are serialized. Repeating an action that already
 * happened is a no-op, never a second answer or a second start.
 *
 * Only the coarse lifecycle is stored on the server: while the interview
 * runs the session stays "asking". Recording, transcribing and reviewing are
 * browser states, because nothing is persisted until the user sends.
 */
class InterviewFlow
{
    public function __construct(
        private readonly Interviewer $interviewer,
        private readonly InterviewUsage $usage,
    ) {}

    public function start(InterviewSession $session): void
    {
        DB::transaction(function () use ($session) {
            $session = $this->lock($session);

            if ($session->status === InterviewStatus::Asking) {
                return;
            }

            if ($session->status !== InterviewStatus::Ready) {
                throw ValidationException::withMessages([
                    'status' => 'Esta entrevista não pode ser iniciada agora.',
                ]);
            }

            // One interview at a time: another one must be finished first.
            if ($session->user->interviewInProgress() !== null) {
                throw ValidationException::withMessages([
                    'status' => 'Você já tem uma entrevista em andamento. Conclua ou encerre essa entrevista antes de começar outra.',
                ]);
            }

            // The allowance counts started interviews, so it is checked again here.
            if ($this->usage->remaining($session->user) === 0) {
                throw ValidationException::withMessages([
                    'usage' => 'Você atingiu o limite de entrevistas deste mês.',
                ]);
            }

            $session->transitionTo(InterviewStatus::Asking);
            $session->started_at = now();
            $session->save();
            $this->usage->recordInterviewStarted($session);

            $this->askNextOrComplete($session, position: 1);
        });
    }

    /**
     * The user confirmed the (possibly edited) transcript of the current turn.
     */
    public function submitAnswer(InterviewSession $session, InterviewTurn $turn, string $transcript): void
    {
        DB::transaction(function () use ($session, $turn, $transcript) {
            $session = $this->lock($session);
            $turn->refresh();

            // Already sent (double click, or a retry after the response was lost).
            if ($turn->isAnswered()) {
                return;
            }

            $this->ensureInProgress($session);

            $turn->answer_transcript = $transcript;
            $turn->answered_at = now();
            $turn->save();

            // Time's up: the answer in progress still counts, then the interview ends.
            if ($this->timeIsUp($session)) {
                $this->complete($session);

                return;
            }

            $this->askNextOrComplete($session, position: $turn->position + 1);
        });
    }

    /**
     * "Repeat the question": counted for context, never penalized.
     */
    public function repeatQuestion(InterviewSession $session, InterviewTurn $turn): void
    {
        DB::transaction(function () use ($session, $turn) {
            $this->ensureInProgress($this->lock($session));

            if (! $turn->isAnswered()) {
                $turn->increment('repeat_count');
            }
        });
    }

    /**
     * The user chose to end early. Answers already sent are kept.
     */
    public function finish(InterviewSession $session): void
    {
        DB::transaction(function () use ($session) {
            $session = $this->lock($session);

            // Already over (repeated click): it may be completed or evaluated by now.
            if ($session->completed_at !== null) {
                return;
            }

            $this->ensureInProgress($session);
            $this->complete($session);
        });
    }

    public function endsAt(InterviewSession $session): ?\DateTimeInterface
    {
        return $session->started_at?->addMinutes($session->duration_minutes->value);
    }

    private function timeIsUp(InterviewSession $session): bool
    {
        return now()->greaterThanOrEqualTo($this->endsAt($session));
    }

    private function askNextOrComplete(InterviewSession $session, int $position): void
    {
        $next = $this->interviewer->nextQuestion($session);

        if ($next === null) {
            $this->complete($session);

            return;
        }

        $session->turns()->create([
            'position' => $position,
            'question' => $next->text,
            'is_follow_up' => $next->isFollowUp,
        ]);
    }

    /**
     * A report failed (or an interview finished before reports existed):
     * generate it again. Repeating the click is harmless.
     */
    public function retryEvaluation(InterviewSession $session): void
    {
        DB::transaction(function () use ($session) {
            $session = $this->lock($session);

            if (in_array($session->status, [InterviewStatus::Evaluating, InterviewStatus::ReportReady], true)) {
                return;
            }

            $canEvaluate = $session->status === InterviewStatus::FailedRetryable
                || ($session->status === InterviewStatus::Completed
                    && $session->turns()->whereNotNull('answered_at')->exists());

            if (! $canEvaluate) {
                throw ValidationException::withMessages([
                    'status' => 'Esta entrevista não tem relatório para gerar.',
                ]);
            }

            $this->evaluate($session);
        });
    }

    private function complete(InterviewSession $session): void
    {
        $session->transitionTo(InterviewStatus::Completed);
        $session->completed_at = now();
        $session->save();

        // Without any answer there is nothing to evaluate.
        if ($session->turns()->whereNotNull('answered_at')->exists()) {
            $this->evaluate($session);
        }
    }

    private function evaluate(InterviewSession $session): void
    {
        $session->transitionTo(InterviewStatus::Evaluating);
        $session->save();

        // Only after the transaction commits, so the job sees the saved answers.
        EvaluateInterview::dispatch($session)->afterCommit();
    }

    private function ensureInProgress(InterviewSession $session): void
    {
        if ($session->status !== InterviewStatus::Asking) {
            throw ValidationException::withMessages([
                'status' => 'Esta entrevista não está mais em andamento.',
            ]);
        }
    }

    private function lock(InterviewSession $session): InterviewSession
    {
        return InterviewSession::query()->lockForUpdate()->findOrFail($session->id);
    }
}
