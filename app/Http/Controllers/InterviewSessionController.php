<?php

namespace App\Http\Controllers;

use App\Enums\InterviewCategory;
use App\Enums\InterviewDuration;
use App\Enums\InterviewLevel;
use App\Enums\InterviewStatus;
use App\Evaluation\Rubric;
use App\Http\Requests\Interview\StoreInterviewSessionRequest;
use App\Interview\InterviewFlow;
use App\Models\InterviewSession;
use App\Models\InterviewTurn;
use App\Services\InterviewUsage;
use App\Speech\PrerecordedPhrases;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class InterviewSessionController extends Controller
{
    private const HISTORY_PER_PAGE = 15;

    /**
     * History: every session of the user, newest first.
     */
    public function index(Request $request): Response
    {
        $sessions = $request->user()->interviewSessions()
            ->latest()
            ->latest('id')
            ->paginate(self::HISTORY_PER_PAGE)
            ->through(fn (InterviewSession $session) => $session->summary());

        return Inertia::render('interviews/Index', [
            'sessions' => $sessions,
        ]);
    }

    /**
     * Deletes a session with its questions, answers and report (section 9).
     * Usage records stay, so deleting never gives the allowance back.
     */
    public function destroy(Request $request, int $interviewSession): RedirectResponse
    {
        $this->findOwnSession($request, $interviewSession)->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Entrevista apagada.']);

        return to_route('interviews.index');
    }

    /**
     * Interview setup: category, level, duration and the data notice.
     */
    public function create(Request $request, InterviewUsage $usage): Response|RedirectResponse
    {
        if ($running = $this->redirectToRunningInterview($request)) {
            return $running;
        }

        $draft = $this->currentOpenSession($request);

        return Inertia::render('interviews/Create', [
            'options' => [
                'categories' => array_map(fn (InterviewCategory $case) => [
                    'value' => $case->value,
                    'label' => $case->label(),
                    'description' => $case->description(),
                ], InterviewCategory::cases()),
                'levels' => array_map(fn (InterviewLevel $case) => [
                    'value' => $case->value,
                    'label' => $case->label(),
                ], InterviewLevel::cases()),
                'durations' => array_map(fn (InterviewDuration $case) => [
                    'value' => $case->value,
                    'label' => $case->label(),
                    'description' => $case->description(),
                ], InterviewDuration::cases()),
            ],
            'draft' => $draft ? [
                'category' => $draft->category->value,
                'level' => $draft->level->value,
                'duration_minutes' => $draft->duration_minutes->value,
            ] : $this->setupFromQuery($request),
            'usage' => $usage->summary($request->user()),
        ]);
    }

    /**
     * Save the setup as the user's single draft session.
     */
    public function store(StoreInterviewSessionRequest $request, InterviewUsage $usage): RedirectResponse
    {
        // A stale setup tab must not create a second interview either.
        if ($running = $this->redirectToRunningInterview($request)) {
            return $running;
        }

        if ($usage->remaining($request->user()) === 0) {
            throw ValidationException::withMessages([
                'usage' => 'Você atingiu o limite de entrevistas deste mês.',
            ]);
        }

        // Reusing the open session makes repeated submits (double click, back
        // and resubmit) update one session instead of creating duplicates.
        $session = $this->currentOpenSession($request) ?? new InterviewSession;
        $session->fill($request->safe()->only(['category', 'level', 'duration_minutes']));

        // A changed setup must go through the microphone and audio checks again.
        if ($session->status === InterviewStatus::Ready) {
            $session->transitionTo(InterviewStatus::Draft);
        }

        $request->user()->interviewSessions()->save($session);

        return to_route('interviews.show', $session);
    }

    /**
     * Session page: the pre-interview checks, the interview itself while it
     * runs, and the transcript and report once it is over.
     */
    public function show(Request $request, int $interviewSession, PrerecordedPhrases $phrases, InterviewFlow $flow): Response
    {
        $session = $this->findOwnSession($request, $interviewSession);

        if ($session->status === InterviewStatus::Asking) {
            return $this->room($session, $phrases, $flow);
        }

        $samplePhrase = config('faladev.speech.sample_phrase');
        $closing = config('faladev.interview.closing');
        $isFinished = $session->completed_at !== null;

        return Inertia::render('interviews/Show', [
            'session' => $this->sessionDetails($session),
            'samplePhrase' => $samplePhrase,
            'sampleAudioUrl' => $phrases->url($samplePhrase),
            'turns' => $isFinished
                ? $session->turns->map(fn (InterviewTurn $turn) => $this->turnDetails($turn, $phrases))->all()
                : [],
            'closing' => $isFinished ? ['text' => $closing, 'audioUrl' => $phrases->url($closing)] : null,
            // Polled by the page while the report is being prepared.
            'evaluation' => $session->status === InterviewStatus::ReportReady
                ? $this->evaluationDetails($session)
                : null,
            'scoreAnchors' => Rubric::anchors(),
            // "Refazer": same setup, prefilled on the setup page.
            'redoQuery' => [
                'category' => $session->category->value,
                'level' => $session->level->value,
                'duration_minutes' => $session->duration_minutes->value,
            ],
        ]);
    }

    /**
     * "Tentar novamente" after a failed report.
     */
    public function retryEvaluation(Request $request, int $interviewSession, InterviewFlow $flow): RedirectResponse
    {
        $flow->retryEvaluation($this->findOwnSession($request, $interviewSession));

        return to_route('interviews.show', $interviewSession);
    }

    /**
     * The user clicked "Iniciar entrevista": the first question is created.
     */
    public function start(Request $request, int $interviewSession, InterviewFlow $flow): RedirectResponse
    {
        $flow->start($this->findOwnSession($request, $interviewSession));

        return to_route('interviews.show', $interviewSession);
    }

    /**
     * The user confirmed they want to end the interview now.
     */
    public function finish(Request $request, int $interviewSession, InterviewFlow $flow): RedirectResponse
    {
        $flow->finish($this->findOwnSession($request, $interviewSession));

        return to_route('interviews.show', $interviewSession);
    }

    private function room(InterviewSession $session, PrerecordedPhrases $phrases, InterviewFlow $flow): Response
    {
        $turns = $session->turns;
        $current = $turns->first(fn (InterviewTurn $turn) => ! $turn->isAnswered());
        $opening = config('faladev.interview.opening');

        return Inertia::render('interviews/Room', [
            'session' => $this->sessionDetails($session),
            // The opening is only played before the first answer.
            'opening' => $turns->contains(fn (InterviewTurn $turn) => $turn->isAnswered())
                ? null
                : ['text' => $opening, 'audioUrl' => $phrases->url($opening)],
            'currentTurn' => $current ? $this->turnDetails($current, $phrases) : null,
            'answeredTurns' => $turns
                ->filter(fn (InterviewTurn $turn) => $turn->isAnswered())
                ->map(fn (InterviewTurn $turn) => $this->turnDetails($turn, $phrases))
                ->values()
                ->all(),
            'endsAt' => $flow->endsAt($session)?->format(DATE_ATOM),
            'maxAnswerSeconds' => (int) config('faladev.interview.max_answer_seconds'),
            // The page warns that the transcript is simulated.
            'sttIsFake' => config('faladev.speech.stt') === 'fake',
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function evaluationDetails(InterviewSession $session): ?array
    {
        $evaluation = $session->evaluation;

        if ($evaluation === null) {
            return null;
        }

        return [
            'isSimulated' => $evaluation->evaluator === 'fake',
            'overallScore' => $evaluation->overall_score,
            'summary' => $evaluation->summary,
            'dimensions' => $evaluation->dimensions,
            'strengths' => $evaluation->strengths,
            'improvements' => $evaluation->improvements,
            'englishFeedback' => $evaluation->english_feedback,
            'nextSteps' => $evaluation->next_steps,
        ];
    }

    /**
     * Valid setup values from the query string ("Refazer entrevista"),
     * ignoring anything that is not a known option.
     *
     * @return array{category: string, level: string, duration_minutes: int}|null
     */
    private function setupFromQuery(Request $request): ?array
    {
        $category = InterviewCategory::tryFrom((string) $request->query('category'));
        $level = InterviewLevel::tryFrom((string) $request->query('level'));
        $duration = InterviewDuration::tryFrom((int) $request->query('duration_minutes'));

        if ($category === null || $level === null || $duration === null) {
            return null;
        }

        return [
            'category' => $category->value,
            'level' => $level->value,
            'duration_minutes' => $duration->value,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function sessionDetails(InterviewSession $session): array
    {
        return [
            ...$session->summary(),
            'canEditSetup' => $session->isOpenForSetup(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function turnDetails(InterviewTurn $turn, PrerecordedPhrases $phrases): array
    {
        return [
            'id' => $turn->id,
            'position' => $turn->position,
            'question' => $turn->question,
            'audioUrl' => $phrases->url($turn->question),
            'isFollowUp' => $turn->is_follow_up,
            'answer' => $turn->answer_transcript,
        ];
    }

    /**
     * The user finished the microphone and audio checks.
     */
    public function markReady(Request $request, int $interviewSession): RedirectResponse
    {
        $session = $this->findOwnSession($request, $interviewSession);

        // Idempotent: a repeated click must not fail.
        if ($session->status !== InterviewStatus::Ready) {
            if (! $session->status->canTransitionTo(InterviewStatus::Ready)) {
                throw ValidationException::withMessages([
                    'status' => 'Esta entrevista não está mais em preparação.',
                ]);
            }

            $session->transitionTo(InterviewStatus::Ready);
            $session->save();
        }

        return to_route('interviews.show', $session);
    }

    /**
     * Other users' sessions respond 404, not 403, so their existence is not revealed.
     */
    private function findOwnSession(Request $request, int $id): InterviewSession
    {
        return $request->user()->interviewSessions()->findOrFail($id);
    }

    /**
     * One interview at a time: setting up a new one leads back to the one
     * that is running, with a notice explaining why.
     */
    private function redirectToRunningInterview(Request $request): ?RedirectResponse
    {
        $running = $request->user()->interviewInProgress();

        if ($running === null) {
            return null;
        }

        Inertia::flash('toast', [
            'type' => 'info',
            'message' => 'Você tem uma entrevista em andamento. Conclua ou encerre essa entrevista antes de começar outra.',
        ]);

        return to_route('interviews.show', $running);
    }

    private function currentOpenSession(Request $request): ?InterviewSession
    {
        return $request->user()->interviewSessions()
            ->whereIn('status', [InterviewStatus::Draft, InterviewStatus::Ready])
            ->latest('id')
            ->first();
    }
}
