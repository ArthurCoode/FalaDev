<?php

use App\Enums\InterviewCategory;
use App\Enums\InterviewDuration;
use App\Enums\InterviewStatus;
use App\Models\InterviewSession;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function startedInterview(array $attributes = []): InterviewSession
{
    $session = InterviewSession::factory()->ready()->create([
        'category' => InterviewCategory::Algorithms,
        'duration_minutes' => InterviewDuration::Short,
        ...$attributes,
    ]);

    test()->actingAs($session->user)->post(route('interviews.start', $session));

    return $session->refresh();
}

function answerCurrent(InterviewSession $session, string $text = 'My answer.'): void
{
    $turn = $session->turns()->whereNull('answered_at')->firstOrFail();

    test()->actingAs($session->user)
        ->post(route('interviews.turns.answer', [$session, $turn]), ['transcript' => $text]);
}

test('starting a ready interview asks the first question and consumes the allowance', function () {
    $session = startedInterview();

    expect($session->status)->toBe(InterviewStatus::Asking)
        ->and($session->started_at)->not->toBeNull()
        ->and($session->turns)->toHaveCount(1)
        ->and($session->turns->first()->position)->toBe(1)
        ->and($session->turns->first()->is_follow_up)->toBeFalse();
});

test('starting twice does not create a second question', function () {
    $session = startedInterview();

    $this->actingAs($session->user)
        ->post(route('interviews.start', $session))
        ->assertSessionHasNoErrors();

    expect($session->turns()->count())->toBe(1);
});

test('a draft cannot be started before the audio checks', function () {
    $session = InterviewSession::factory()->create();

    $this->actingAs($session->user)
        ->post(route('interviews.start', $session))
        ->assertSessionHasErrors('status');

    expect($session->fresh()->status)->toBe(InterviewStatus::Draft);
});

test('the monthly limit is checked again when starting', function () {
    config(['faladev.usage.monthly_session_limit' => 1]);
    $user = User::factory()->create();
    InterviewSession::factory()->for($user)->completed()->create();
    $session = InterviewSession::factory()->for($user)->ready()->create();

    $this->actingAs($user)
        ->post(route('interviews.start', $session))
        ->assertSessionHasErrors('usage');

    expect($session->fresh()->status)->toBe(InterviewStatus::Ready);
});

test('the running interview page shows the current question and the opening', function () {
    $session = startedInterview();

    $this->actingAs($session->user)
        ->get(route('interviews.show', $session))
        ->assertInertia(fn (Assert $page) => $page
            ->component('interviews/Room')
            ->where('currentTurn.position', 1)
            ->where('opening.text', config('faladev.interview.opening'))
            ->has('answeredTurns', 0)
            ->where('sttIsFake', true));
});

test('answering a main question leads to a follow-up, then to the next main question', function () {
    $session = startedInterview();

    answerCurrent($session);
    answerCurrent($session);

    $turns = $session->turns()->get();
    expect($turns)->toHaveCount(3)
        ->and($turns[0]->answer_transcript)->toBe('My answer.')
        ->and($turns[1]->is_follow_up)->toBeTrue()
        ->and($turns[2]->is_follow_up)->toBeFalse()
        ->and($turns[2]->position)->toBe(3);
});

test('after the first answer the opening is no longer played', function () {
    $session = startedInterview();
    answerCurrent($session);

    $this->actingAs($session->user)
        ->get(route('interviews.show', $session))
        ->assertInertia(fn (Assert $page) => $page
            ->where('opening', null)
            ->has('answeredTurns', 1));
});

test('sending the same answer twice records it once', function () {
    $session = startedInterview();
    $turn = $session->turns()->first();

    foreach (['First send.', 'Retry after a dropped connection.'] as $text) {
        $this->actingAs($session->user)
            ->post(route('interviews.turns.answer', [$session, $turn]), ['transcript' => $text])
            ->assertRedirect(route('interviews.show', $session));
    }

    expect($turn->fresh()->answer_transcript)->toBe('First send.')
        ->and($session->turns()->count())->toBe(2);
});

test('an empty answer is rejected', function () {
    $session = startedInterview();
    $turn = $session->turns()->first();

    $this->actingAs($session->user)
        ->post(route('interviews.turns.answer', [$session, $turn]), ['transcript' => '   '])
        ->assertSessionHasErrors('transcript');

    expect($turn->fresh()->isAnswered())->toBeFalse();
});

test('a 15-minute interview ends after three questions and their follow-ups, then gets its report', function () {
    $session = startedInterview();

    foreach (range(1, 6) as $answer) {
        answerCurrent($session);
    }

    $session->refresh();
    expect($session->status)->toBe(InterviewStatus::ReportReady)
        ->and($session->completed_at)->not->toBeNull()
        ->and($session->turns()->count())->toBe(6)
        ->and($session->turns()->whereNull('answered_at')->count())->toBe(0);
});

test('a 30-minute interview has four main questions', function () {
    $session = startedInterview(['duration_minutes' => InterviewDuration::Long]);

    foreach (range(1, 8) as $answer) {
        answerCurrent($session);
    }

    expect($session->fresh()->status)->toBe(InterviewStatus::ReportReady)
        ->and($session->turns()->where('is_follow_up', false)->count())->toBe(4);
});

test('when time is up the answer in progress still counts, then the interview ends', function () {
    $session = startedInterview();
    $this->travel(16)->minutes();

    answerCurrent($session, 'Late but complete answer.');

    $session->refresh();
    expect($session->status)->toBe(InterviewStatus::ReportReady)
        ->and($session->turns()->count())->toBe(1)
        ->and($session->turns()->first()->answer_transcript)->toBe('Late but complete answer.');
});

test('ending early keeps the answers already sent', function () {
    $session = startedInterview();
    answerCurrent($session);

    $this->actingAs($session->user)
        ->post(route('interviews.finish', $session))
        ->assertRedirect(route('interviews.show', $session));

    $session->refresh();
    expect($session->status)->toBe(InterviewStatus::ReportReady)
        ->and($session->turns()->whereNotNull('answered_at')->count())->toBe(1);
});

test('the finished page shows the transcript and the closing line', function () {
    $session = startedInterview();
    answerCurrent($session, 'An answer to keep.');
    $this->actingAs($session->user)->post(route('interviews.finish', $session));

    $this->actingAs($session->user)
        ->get(route('interviews.show', $session))
        ->assertInertia(fn (Assert $page) => $page
            ->component('interviews/Show')
            ->where('closing.text', config('faladev.interview.closing'))
            ->has('turns', 2)
            ->where('turns.0.answer', 'An answer to keep.')
            ->where('turns.1.answer', null));
});

test('no answer can be sent after the interview ended', function () {
    $session = startedInterview();
    $this->actingAs($session->user)->post(route('interviews.finish', $session));
    $turn = $session->turns()->first();

    $this->actingAs($session->user)
        ->post(route('interviews.turns.answer', [$session, $turn]), ['transcript' => 'Too late.'])
        ->assertSessionHasErrors('status');

    expect($turn->fresh()->isAnswered())->toBeFalse();
});

test('repeating the question is counted without other effects', function () {
    $session = startedInterview();
    $turn = $session->turns()->first();

    $this->actingAs($session->user)->post(route('interviews.turns.repeat', [$session, $turn]))->assertNoContent();
    $this->actingAs($session->user)->post(route('interviews.turns.repeat', [$session, $turn]))->assertNoContent();

    expect($turn->fresh()->repeat_count)->toBe(2)
        ->and($session->turns()->count())->toBe(1);
});

test("users cannot act on another user's interview", function () {
    $session = startedInterview();
    $turn = $session->turns()->first();
    $other = User::factory()->create();

    $this->actingAs($other)->post(route('interviews.start', $session))->assertNotFound();
    $this->actingAs($other)->post(route('interviews.finish', $session))->assertNotFound();
    $this->actingAs($other)
        ->post(route('interviews.turns.answer', [$session, $turn]), ['transcript' => 'Not mine.'])
        ->assertNotFound();
    $this->actingAs($other)->post(route('interviews.turns.repeat', [$session, $turn]))->assertNotFound();

    expect($session->fresh()->status)->toBe(InterviewStatus::Asking)
        ->and($turn->fresh()->isAnswered())->toBeFalse();
});

test('a turn must belong to the session in the URL', function () {
    $mine = startedInterview();
    $otherTurn = startedInterview()->turns()->first();

    $this->actingAs($mine->user)
        ->post(route('interviews.turns.answer', [$mine, $otherTurn]), ['transcript' => 'Wrong turn.'])
        ->assertNotFound();
});

test('a second interview cannot start while another one is running', function () {
    $running = startedInterview();
    $other = InterviewSession::factory()->for($running->user)->ready()->create();

    $this->actingAs($running->user)
        ->post(route('interviews.start', $other))
        ->assertSessionHasErrors('status');

    expect($other->fresh()->status)->toBe(InterviewStatus::Ready)
        ->and($other->fresh()->started_at)->toBeNull();
});

test('the setup page leads back to the running interview', function () {
    $running = startedInterview();

    $this->actingAs($running->user)
        ->get(route('interviews.create'))
        ->assertRedirect(route('interviews.show', $running));
});

test('saving a setup from a stale tab does not create another interview', function () {
    $running = startedInterview();

    $this->actingAs($running->user)->post(route('interviews.store'), [
        'category' => 'behavioral',
        'level' => 'mid',
        'duration_minutes' => '15',
        'data_notice_accepted' => 'on',
    ])->assertRedirect(route('interviews.show', $running));

    expect($running->user->interviewSessions()->count())->toBe(1);
});

test('a new interview can be set up once the running one is finished', function () {
    $running = startedInterview();
    $this->actingAs($running->user)->post(route('interviews.finish', $running));

    $this->actingAs($running->user)->get(route('interviews.create'))->assertOk();
});
