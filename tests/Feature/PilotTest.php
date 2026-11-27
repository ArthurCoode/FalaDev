<?php

use App\Enums\InterviewStatus;
use App\Enums\PilotEventType;
use App\Evaluation\Contracts\Evaluator;
use App\Evaluation\EvaluationResult;
use App\Evaluation\Exceptions\EvaluationFailed;
use App\Models\InterviewSession;
use App\Models\InterviewTurn;
use App\Models\PilotEvent;
use App\Models\User;
use App\Speech\Contracts\SpeechToText;
use App\Speech\Exceptions\TranscriptionFailed;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;

function pilotAdmin(): User
{
    config(['faladev.pilot.admins' => ['admin@faladev.test']]);

    return User::factory()->create(['email' => 'Admin@FalaDev.test']);
}

function reportedInterview(): InterviewSession
{
    $session = InterviewSession::factory()->ready()->create();
    test()->actingAs($session->user)->post(route('interviews.start', $session));
    $turn = $session->turns()->firstOrFail();
    test()->actingAs($session->user)->post(route('interviews.turns.answer', [$session, $turn]), ['transcript' => 'An answer.']);
    test()->actingAs($session->user)->post(route('interviews.finish', $session));

    return $session->refresh();
}

// --- Technical events from the browser --------------------------------------

test('the browser can report a microphone check', function () {
    $session = InterviewSession::factory()->create();

    $this->actingAs($session->user)
        ->post(route('pilot.events.store'), [
            'type' => 'microphone_check',
            'passed' => '1',
            'peak' => '0.23456',
            'interview_session_id' => $session->id,
        ])
        ->assertNoContent();

    $event = PilotEvent::sole();
    expect($event->type)->toBe(PilotEventType::MicrophoneCheck)
        ->and($event->data)->toBe(['passed' => true, 'peak' => 0.235])
        ->and($event->interview_session_id)->toBe($session->id);
});

test('the browser can report a microphone error code', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('pilot.events.store'), ['type' => 'microphone_error', 'error' => 'permission_denied'])
        ->assertNoContent();

    expect(PilotEvent::sole()->data)->toBe(['error' => 'permission_denied']);
});

test('the browser cannot send server-only event types or unknown error codes', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('pilot.events.store'), ['type' => 'evaluation_failed'])
        ->assertJsonValidationErrors('type');
    $this->actingAs($user)
        ->postJson(route('pilot.events.store'), ['type' => 'microphone_error', 'error' => '<script>'])
        ->assertJsonValidationErrors('error');

    expect(PilotEvent::count())->toBe(0);
});

test("another user's session is not linked to an event", function () {
    $user = User::factory()->create();
    $someoneElses = InterviewSession::factory()->create();

    $this->actingAs($user)->post(route('pilot.events.store'), [
        'type' => 'microphone_error',
        'error' => 'no_device',
        'interview_session_id' => $someoneElses->id,
    ])->assertNoContent();

    expect(PilotEvent::sole()->interview_session_id)->toBeNull();
});

test('guests cannot send events', function () {
    $this->post(route('pilot.events.store'), ['type' => 'microphone_error', 'error' => 'no_device'])
        ->assertRedirect(route('login'));
});

// --- Events recorded by the server ------------------------------------------

test('a transcription failure is recorded', function () {
    app()->instance(SpeechToText::class, new class implements SpeechToText
    {
        public function transcribe(string $path, string $mimeType): string
        {
            throw new TranscriptionFailed('down');
        }
    });
    $session = InterviewSession::factory()->ready()->create();
    $this->actingAs($session->user)->post(route('interviews.start', $session));
    $turn = $session->turns()->firstOrFail();

    $this->actingAs($session->user)->post(
        route('interviews.turns.transcribe', [$session, $turn]),
        ['audio' => UploadedFile::fake()->create('a.webm', 10, 'audio/webm')],
        ['Accept' => 'application/json'],
    )->assertStatus(503);

    expect(PilotEvent::sole()->type)->toBe(PilotEventType::TranscriptionFailed);
});

test('a report failure is recorded', function () {
    app()->instance(Evaluator::class, new class implements Evaluator
    {
        public function name(): string
        {
            return 'failing';
        }

        public function evaluate(InterviewSession $session): EvaluationResult
        {
            throw new EvaluationFailed('down');
        }
    });

    $session = reportedInterview();

    expect($session->status)->toBe(InterviewStatus::FailedRetryable)
        ->and(PilotEvent::sole()->type)->toBe(PilotEventType::EvaluationFailed);
});

// --- "Este feedback foi útil?" ----------------------------------------------

test('the user can rate a report and change the answer later', function () {
    $session = reportedInterview();

    $this->actingAs($session->user)
        ->post(route('interviews.evaluation.feedback', $session), ['useful' => '1', 'comment' => '  Clear and concrete.  '])
        ->assertSessionHasNoErrors();
    $this->actingAs($session->user)
        ->post(route('interviews.evaluation.feedback', $session), ['useful' => '0']);

    $evaluation = $session->evaluation()->first();
    expect($evaluation->user_found_useful)->toBeFalse()
        ->and($evaluation->user_comment)->toBeNull()
        ->and($evaluation->user_rated_at)->not->toBeNull();

    $this->actingAs($session->user)
        ->get(route('interviews.show', $session))
        ->assertInertia(fn (Assert $page) => $page->where('evaluation.userFeedback.useful', false));
});

test('comments are limited to 500 characters', function () {
    $session = reportedInterview();

    $this->actingAs($session->user)
        ->post(route('interviews.evaluation.feedback', $session), ['useful' => '1', 'comment' => str_repeat('a', 501)])
        ->assertSessionHasErrors('comment');
});

test('a report that is not ready cannot be rated', function () {
    $session = InterviewSession::factory()->ready()->create();

    $this->actingAs($session->user)
        ->post(route('interviews.evaluation.feedback', $session), ['useful' => '1'])
        ->assertSessionHasErrors('useful');
});

test("users cannot rate another user's report", function () {
    $session = reportedInterview();

    $this->actingAs(User::factory()->create())
        ->post(route('interviews.evaluation.feedback', $session), ['useful' => '1'])
        ->assertNotFound();
});

// --- Metrics page -----------------------------------------------------------

test('only pilot admins can open the metrics page', function () {
    $admin = pilotAdmin();

    $this->actingAs(User::factory()->create())->get(route('pilot.metrics'))->assertNotFound();
    $this->actingAs($admin)->get(route('pilot.metrics'))->assertOk();
});

test('the menu item is shown only to pilot admins', function () {
    $admin = pilotAdmin();

    $this->actingAs($admin)->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('auth.canViewPilotMetrics', true));
    $this->actingAs(User::factory()->create())->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('auth.canViewPilotMetrics', false));
});

test('the funnel counts how far sessions got', function () {
    $admin = pilotAdmin();
    InterviewSession::factory()->create(); // configured only
    InterviewSession::factory()->ready()->create(); // checks done
    $reported = reportedInterview(); // all the way
    $reported->evaluation->update(['user_found_useful' => true, 'user_comment' => 'Great', 'user_rated_at' => now()]);

    $this->actingAs($admin)
        ->get(route('pilot.metrics'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('pilot/Metrics')
            ->where('metrics.funnel.0.count', 3) // configured
            ->where('metrics.funnel.1.count', 2) // checks done
            ->where('metrics.funnel.2.count', 1) // started
            ->where('metrics.funnel.3.count', 1) // completed
            ->where('metrics.funnel.4.count', 1) // completed with an answer
            ->where('metrics.funnel.5.count', 1) // reported
            ->where('metrics.answers.perCompletedAvg', 1)
            ->where('metrics.usefulness.useful', 1)
            ->where('metrics.usefulness.comments.0.comment', 'Great'));
});

test('microphone peaks are grouped to calibrate the test threshold', function () {
    $admin = pilotAdmin();
    foreach ([[false, 0.02], [false, 0.12], [true, 0.3], [true, 0.31]] as [$passed, $peak]) {
        PilotEvent::record(PilotEventType::MicrophoneCheck, $admin->id, null, ['passed' => $passed, 'peak' => $peak]);
    }
    PilotEvent::record(PilotEventType::MicrophoneError, $admin->id, null, ['error' => 'no_device']);

    $this->actingAs($admin)
        ->get(route('pilot.metrics'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('metrics.microphone.checks', 4)
            ->where('metrics.microphone.checksPassed', 2)
            ->where('metrics.microphone.peakBuckets.0.failed', 1) // 0.00–0.05
            ->where('metrics.microphone.peakBuckets.2.failed', 1) // 0.10–0.15
            ->where('metrics.microphone.peakBuckets.4.passed', 2) // 0.25–0.50
            ->where('metrics.microphone.errors.no_device', 1));
});

test('the period filter leaves older sessions out', function () {
    $admin = pilotAdmin();
    InterviewSession::factory()->create(['created_at' => now()->subDays(40)]);
    InterviewSession::factory()->create();

    $this->actingAs($admin)->get(route('pilot.metrics', ['days' => 30]))
        ->assertInertia(fn (Assert $page) => $page->where('days', 30)->where('metrics.funnel.0.count', 1));
    $this->actingAs($admin)->get(route('pilot.metrics', ['days' => 0]))
        ->assertInertia(fn (Assert $page) => $page->where('metrics.funnel.0.count', 2));
    $this->actingAs($admin)->get(route('pilot.metrics', ['days' => 12345]))
        ->assertInertia(fn (Assert $page) => $page->where('days', 30));
});

test('transcription requests are counted from turn metadata', function () {
    $admin = pilotAdmin();
    $session = InterviewSession::factory()->create();
    InterviewTurn::factory()->for($session, 'session')->create(['metadata' => ['transcriptions' => 3]]);

    $this->actingAs($admin)->get(route('pilot.metrics'))
        ->assertInertia(fn (Assert $page) => $page->where('metrics.failures.transcriptionRequests', 3));
});

test('each funnel stage is a subset of the previous one', function () {
    $admin = pilotAdmin();
    reportedInterview();
    // Ended without answering: completed, but no answer and no report.
    $empty = InterviewSession::factory()->ready()->create();
    $this->actingAs($empty->user)->post(route('interviews.start', $empty));
    $this->actingAs($empty->user)->post(route('interviews.finish', $empty));

    $this->actingAs($admin)
        ->get(route('pilot.metrics'))
        ->assertInertia(function (Assert $page) {
            $counts = array_column($page->toArray()['props']['metrics']['funnel'], 'count');

            expect($counts)->toBe([2, 2, 2, 2, 1, 1]);
        });
});
