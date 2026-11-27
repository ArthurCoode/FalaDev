<?php

namespace App\Services;

use App\Enums\InterviewStatus;
use App\Enums\PilotEventType;
use App\Models\Evaluation;
use App\Models\InterviewSession;
use App\Models\InterviewTurn;
use App\Models\PilotEvent;
use DateTimeInterface;
use Illuminate\Support\Collection;

/**
 * Pilot metrics (cursor/skills/FalaDev.md, section 12), computed on demand.
 * Volumes during the pilot are small, so plain queries and PHP math are
 * enough; no aggregation tables. Numbers are reported as observed, with no
 * targets (the document says not to set targets before the first group).
 */
class PilotMetrics
{
    /** Microphone peak buckets, to calibrate MIN_PEAK_LEVEL in MicrophoneCheck.vue. */
    private const PEAK_BUCKETS = [0.05, 0.1, 0.15, 0.25, 0.5, 1.0];

    /**
     * @return array<string, mixed>
     */
    public function summary(?DateTimeInterface $since): array
    {
        $sessions = InterviewSession::query()
            ->when($since, fn ($query) => $query->where('created_at', '>=', $since))
            ->with('evaluation')
            ->withCount(['turns as answered_count' => fn ($query) => $query->whereNotNull('answered_at')])
            ->get();

        $events = PilotEvent::query()
            ->when($since, fn ($query) => $query->where('created_at', '>=', $since))
            ->get();

        return [
            'funnel' => $this->funnel($sessions),
            'durations' => $this->durations($sessions),
            'answers' => $this->answers($sessions),
            'returning' => $this->returning($sessions),
            'microphone' => $this->microphone($events),
            'failures' => [
                'transcriptionRequests' => $this->transcriptionRequests($sessions),
                'transcriptionFailures' => $events->where('type', PilotEventType::TranscriptionFailed)->count(),
                'evaluationFailures' => $events->where('type', PilotEventType::EvaluationFailed)->count(),
                'reportsAwaitingRetry' => $sessions->where('status', InterviewStatus::FailedRetryable)->count(),
            ],
            'usefulness' => $this->usefulness($sessions),
        ];
    }

    /**
     * Each stage counts the sessions that reached it, and each one is a subset
     * of the previous. "Testes concluídos" is approximate: a ready session
     * whose setup changed goes back to draft.
     *
     * @param  Collection<int, InterviewSession>  $sessions
     * @return list<array{key: string, label: string, count: int}>
     */
    private function funnel(Collection $sessions): array
    {
        $started = $sessions->whereNotNull('started_at');
        $completed = $sessions->whereNotNull('completed_at');

        return [
            ['key' => 'configured', 'label' => 'Configuradas', 'count' => $sessions->count()],
            ['key' => 'checked', 'label' => 'Testes de áudio concluídos', 'count' => $sessions
                ->filter(fn (InterviewSession $s) => $s->started_at !== null || $s->status === InterviewStatus::Ready)
                ->count()],
            ['key' => 'started', 'label' => 'Iniciadas', 'count' => $started->count()],
            ['key' => 'completed', 'label' => 'Concluídas', 'count' => $completed->count()],
            ['key' => 'answered', 'label' => 'Concluídas com resposta', 'count' => $completed->where('answered_count', '>', 0)->count()],
            ['key' => 'reported', 'label' => 'Com relatório', 'count' => $sessions->whereNotNull('evaluation')->count()],
        ];
    }

    /**
     * @param  Collection<int, InterviewSession>  $sessions
     * @return array{interviewMinutesAvg: float|null, reportSecondsMedian: float|null, reportSecondsMax: float|null}
     */
    private function durations(Collection $sessions): array
    {
        $completed = $sessions->filter(fn (InterviewSession $s) => $s->started_at && $s->completed_at);
        $interviewMinutes = $completed->map(fn (InterviewSession $s) => $s->started_at->diffInSeconds($s->completed_at) / 60);

        $reportSeconds = $sessions
            ->filter(fn (InterviewSession $s) => $s->completed_at && $s->evaluation)
            ->map(fn (InterviewSession $s) => max($s->completed_at->diffInSeconds($s->evaluation->created_at), 0));

        return [
            'interviewMinutesAvg' => $interviewMinutes->isEmpty() ? null : round($interviewMinutes->avg(), 1),
            'reportSecondsMedian' => $reportSeconds->isEmpty() ? null : round((float) $reportSeconds->median(), 1),
            'reportSecondsMax' => $reportSeconds->isEmpty() ? null : round((float) $reportSeconds->max(), 1),
        ];
    }

    /**
     * @param  Collection<int, InterviewSession>  $sessions
     * @return array{perCompletedAvg: float|null, repeatsPerQuestion: float|null}
     */
    private function answers(Collection $sessions): array
    {
        $completed = $sessions->whereNotNull('completed_at');
        $turns = InterviewTurn::query()->whereIn('interview_session_id', $sessions->pluck('id')->all())->get(['repeat_count']);

        return [
            'perCompletedAvg' => $completed->isEmpty() ? null : round($completed->avg('answered_count'), 1),
            'repeatsPerQuestion' => $turns->isEmpty() ? null : round($turns->avg('repeat_count'), 2),
        ];
    }

    /**
     * People who came back: started two or more interviews in the period.
     *
     * @param  Collection<int, InterviewSession>  $sessions
     * @return array{users: int, returningUsers: int}
     */
    private function returning(Collection $sessions): array
    {
        $startsPerUser = $sessions->whereNotNull('started_at')->countBy('user_id');

        return [
            'users' => $startsPerUser->count(),
            'returningUsers' => $startsPerUser->filter(fn (int $starts) => $starts >= 2)->count(),
        ];
    }

    /**
     * @param  Collection<int, PilotEvent>  $events
     * @return array<string, mixed>
     */
    private function microphone(Collection $events): array
    {
        $checks = $events->where('type', PilotEventType::MicrophoneCheck);
        $buckets = [];
        $lower = 0.0;

        foreach (self::PEAK_BUCKETS as $upper) {
            $inBucket = $checks->filter(function (PilotEvent $e) use ($lower, $upper) {
                $peak = (float) ($e->data['peak'] ?? 0);

                return $peak >= $lower && ($peak < $upper || ($upper === 1.0 && $peak <= 1.0));
            });

            $buckets[] = [
                'range' => sprintf('%.2f–%.2f', $lower, $upper),
                'passed' => $inBucket->filter(fn (PilotEvent $e) => ($e->data['passed'] ?? false) === true)->count(),
                'failed' => $inBucket->filter(fn (PilotEvent $e) => ($e->data['passed'] ?? false) !== true)->count(),
            ];
            $lower = $upper;
        }

        return [
            'checks' => $checks->count(),
            'checksPassed' => $checks->filter(fn (PilotEvent $e) => ($e->data['passed'] ?? false) === true)->count(),
            'peakBuckets' => $buckets,
            'errors' => $events->where('type', PilotEventType::MicrophoneError)
                ->countBy(fn (PilotEvent $e) => (string) ($e->data['error'] ?? 'unknown'))
                ->sortDesc()
                ->all(),
        ];
    }

    /**
     * @param  Collection<int, InterviewSession>  $sessions
     */
    private function transcriptionRequests(Collection $sessions): int
    {
        return (int) InterviewTurn::query()
            ->whereIn('interview_session_id', $sessions->pluck('id')->all())
            ->get(['metadata'])
            ->sum(fn (InterviewTurn $turn) => (int) ($turn->metadata['transcriptions'] ?? 0));
    }

    /**
     * @param  Collection<int, InterviewSession>  $sessions
     * @return array<string, mixed>
     */
    private function usefulness(Collection $sessions): array
    {
        /** @var Collection<int, Evaluation> $rated */
        $rated = $sessions->pluck('evaluation')->filter(fn (?Evaluation $e) => $e?->user_rated_at !== null);

        return [
            'reports' => $sessions->whereNotNull('evaluation')->count(),
            'rated' => $rated->count(),
            'useful' => $rated->where('user_found_useful', true)->count(),
            'comments' => $rated
                ->filter(fn (Evaluation $e) => filled($e->user_comment))
                ->sortByDesc('user_rated_at')
                ->take(10)
                ->map(fn (Evaluation $e) => [
                    'useful' => (bool) $e->user_found_useful,
                    'comment' => $e->user_comment,
                    'date' => $e->user_rated_at?->toIso8601String(),
                ])
                ->values()
                ->all(),
        ];
    }
}
