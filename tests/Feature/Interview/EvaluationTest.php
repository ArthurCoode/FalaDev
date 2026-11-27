<?php

use App\Enums\InterviewCategory;
use App\Enums\InterviewDuration;
use App\Enums\InterviewStatus;
use App\Evaluation\Contracts\Evaluator;
use App\Evaluation\EvaluationResult;
use App\Evaluation\Exceptions\EvaluationFailed;
use App\Jobs\EvaluateInterview;
use App\Models\Evaluation;
use App\Models\InterviewSession;
use App\Models\InterviewTurn;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;

function interviewWithAnswers(array $answers = ['I would use a hash map. It gives constant lookups.']): InterviewSession
{
    $session = InterviewSession::factory()->ready()->create([
        'category' => InterviewCategory::Algorithms,
        'duration_minutes' => InterviewDuration::Short,
    ]);
    test()->actingAs($session->user)->post(route('interviews.start', $session));

    foreach ($answers as $answer) {
        $turn = $session->turns()->whereNull('answered_at')->firstOrFail();
        test()->actingAs($session->user)
            ->post(route('interviews.turns.answer', [$session, $turn]), ['transcript' => $answer]);
    }

    return $session;
}

function failingEvaluator(): void
{
    app()->instance(Evaluator::class, new class implements Evaluator
    {
        public function name(): string
        {
            return 'failing';
        }

        public function evaluate(InterviewSession $session): EvaluationResult
        {
            throw new EvaluationFailed('provider down');
        }
    });
}

test('finishing an interview with answers queues its evaluation', function () {
    Queue::fake();
    $session = interviewWithAnswers();

    $this->actingAs($session->user)->post(route('interviews.finish', $session));

    expect($session->fresh()->status)->toBe(InterviewStatus::Evaluating);
    Queue::assertPushed(EvaluateInterview::class, fn ($job) => $job->session->is($session));
});

test('the report is saved and the session becomes report_ready', function () {
    $session = interviewWithAnswers();

    $this->actingAs($session->user)->post(route('interviews.finish', $session));

    $evaluation = $session->fresh()->evaluation;
    expect($session->fresh()->status)->toBe(InterviewStatus::ReportReady)
        ->and($evaluation->evaluator)->toBe('fake')
        ->and($evaluation->overall_score)->toBe(3)
        ->and(array_column($evaluation->dimensions, 'key'))->toBe(['technical', 'reasoning', 'communication', 'english']);
});

test('the fake report quotes the user answers as evidence and says it is simulated', function () {
    $session = interviewWithAnswers(['I would use a hash map. It gives constant lookups.', 'Linear time and linear space.']);

    $this->actingAs($session->user)->post(route('interviews.finish', $session));

    $evaluation = $session->fresh()->evaluation;
    expect($evaluation->summary)->toContain('simulado')
        ->and($evaluation->strengths[0]['evidence'])->toBe('I would use a hash map. It gives constant lookups.')
        ->and($evaluation->strengths[0]['position'])->toBe(1)
        ->and($evaluation->improvements[0]['evidence'])->toBe('Linear time and linear space.')
        ->and($evaluation->english_feedback[0]['original'])->toBe('I would use a hash map.');
});

test('the report page shows the evaluation with the score scale', function () {
    $session = interviewWithAnswers();
    $this->actingAs($session->user)->post(route('interviews.finish', $session));

    $this->actingAs($session->user)
        ->get(route('interviews.show', $session))
        ->assertInertia(fn (Assert $page) => $page
            ->component('interviews/Show')
            ->where('evaluation.isSimulated', true)
            ->where('evaluation.overallScore', 3)
            ->has('evaluation.dimensions', 4)
            ->where('scoreAnchors.1', 'insuficiente')
            ->where('scoreAnchors.5', 'forte para o nível selecionado')
            ->where('redoQuery.category', 'algorithms'));
});

test('an interview ended without answers is not evaluated', function () {
    Queue::fake();
    $session = interviewWithAnswers([]);

    $this->actingAs($session->user)->post(route('interviews.finish', $session));

    expect($session->fresh()->status)->toBe(InterviewStatus::Completed);
    Queue::assertNothingPushed();
});

test('a failed evaluation keeps the transcript and can be retried', function () {
    failingEvaluator();
    $session = interviewWithAnswers();

    $this->actingAs($session->user)->post(route('interviews.finish', $session));

    expect($session->fresh()->status)->toBe(InterviewStatus::FailedRetryable)
        ->and($session->evaluation()->exists())->toBeFalse()
        ->and($session->turns()->whereNotNull('answered_at')->count())->toBe(1);

    app()->forgetInstance(Evaluator::class);
    $this->actingAs($session->user)
        ->post(route('interviews.evaluation.retry', $session))
        ->assertRedirect(route('interviews.show', $session));

    expect($session->fresh()->status)->toBe(InterviewStatus::ReportReady)
        ->and($session->evaluation()->exists())->toBeTrue();
});

test('the page shows no evaluation while it failed', function () {
    failingEvaluator();
    $session = interviewWithAnswers();
    $this->actingAs($session->user)->post(route('interviews.finish', $session));

    $this->actingAs($session->user)
        ->get(route('interviews.show', $session))
        ->assertInertia(fn (Assert $page) => $page
            ->where('session.status', 'failed_retryable')
            ->where('evaluation', null));
});

test('an interview finished before reports existed can get one', function () {
    $session = InterviewSession::factory()->create([
        'status' => InterviewStatus::Completed,
        'started_at' => now()->subMinutes(20),
        'completed_at' => now(),
    ]);
    InterviewTurn::factory()->answered()->for($session, 'session')->create();

    $this->actingAs($session->user)->post(route('interviews.evaluation.retry', $session));

    expect($session->fresh()->status)->toBe(InterviewStatus::ReportReady);
});

test('retrying is refused when there is nothing to evaluate', function () {
    $session = InterviewSession::factory()->ready()->create();

    $this->actingAs($session->user)
        ->post(route('interviews.evaluation.retry', $session))
        ->assertSessionHasErrors('status');
});

test('retrying a ready report does nothing', function () {
    $session = interviewWithAnswers();
    $this->actingAs($session->user)->post(route('interviews.finish', $session));
    $evaluationId = $session->fresh()->evaluation->id;

    $this->actingAs($session->user)
        ->post(route('interviews.evaluation.retry', $session))
        ->assertSessionHasNoErrors();

    expect($session->fresh()->evaluation->id)->toBe($evaluationId);
});

test("users cannot retry another user's evaluation", function () {
    failingEvaluator();
    $session = interviewWithAnswers();
    $this->actingAs($session->user)->post(route('interviews.finish', $session));

    $this->actingAs(User::factory()->create())
        ->post(route('interviews.evaluation.retry', $session))
        ->assertNotFound();
});

test('a job for a deleted interview is discarded', function () {
    Queue::fake();
    $session = interviewWithAnswers();
    $this->actingAs($session->user)->post(route('interviews.finish', $session));
    $session->delete();

    expect(Evaluation::count())->toBe(0);
    expect((new EvaluateInterview($session))->deleteWhenMissingModels)->toBeTrue();
});

test('deleting an interview deletes its report', function () {
    $session = interviewWithAnswers();
    $this->actingAs($session->user)->post(route('interviews.finish', $session));

    $this->actingAs($session->user)->delete(route('interviews.destroy', $session));

    expect(Evaluation::count())->toBe(0);
});

test('scores outside 1–5 are rejected instead of shown', function () {
    new EvaluationResult(
        overallScore: 6,
        summary: 'x',
        dimensions: [['key' => 'technical', 'label' => 'x', 'score' => 3, 'explanation' => 'x']],
        strengths: [],
        improvements: [],
        englishFeedback: [],
        nextSteps: [],
    );
})->throws(InvalidArgumentException::class);

test('the setup page can be prefilled to redo an interview', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('interviews.create', ['category' => 'system_design', 'level' => 'senior', 'duration_minutes' => 30]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('draft.category', 'system_design')
            ->where('draft.level', 'senior')
            ->where('draft.duration_minutes', 30));
});

test('invalid prefill values are ignored', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('interviews.create', ['category' => 'cooking', 'level' => 'senior', 'duration_minutes' => 30]))
        ->assertInertia(fn (Assert $page) => $page->where('draft', null));
});
