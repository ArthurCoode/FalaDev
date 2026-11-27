<?php

use App\Enums\InterviewCategory;
use App\Enums\InterviewDuration;
use App\Enums\InterviewLevel;
use App\Enums\InterviewStatus;
use App\Models\InterviewSession;
use App\Models\InterviewTurn;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;

test('a new session starts as draft with typed attributes', function () {
    $user = User::factory()->create();

    $session = $user->interviewSessions()->create([
        'category' => InterviewCategory::SystemDesign,
        'level' => InterviewLevel::Mid,
        'duration_minutes' => InterviewDuration::Short,
    ])->fresh();

    expect($session->status)->toBe(InterviewStatus::Draft)
        ->and($session->category)->toBe(InterviewCategory::SystemDesign)
        ->and($session->level)->toBe(InterviewLevel::Mid)
        ->and($session->duration_minutes)->toBe(InterviewDuration::Short);
});

test('status cannot be mass assigned', function () {
    $session = new InterviewSession(['status' => InterviewStatus::ReportReady]);

    expect($session->status)->toBe(InterviewStatus::Draft);
});

test('an unsupported duration is rejected', function () {
    InterviewSession::factory()->create(['duration_minutes' => 45]);
})->throws(ValueError::class);

test('turns are returned in position order', function () {
    $session = InterviewSession::factory()->create();
    InterviewTurn::factory()->for($session, 'session')->create(['position' => 2]);
    InterviewTurn::factory()->for($session, 'session')->create(['position' => 1]);

    expect($session->turns->pluck('position')->all())->toBe([1, 2]);
});

test('a session cannot have two turns in the same position', function () {
    $session = InterviewSession::factory()->create();
    InterviewTurn::factory()->for($session, 'session')->create(['position' => 1]);
    InterviewTurn::factory()->for($session, 'session')->create(['position' => 1]);
})->throws(UniqueConstraintViolationException::class);

test('deleting a user removes their sessions and turns', function () {
    $turn = InterviewTurn::factory()->answered()->create();
    $user = $turn->session->user;

    $user->delete();

    expect(InterviewSession::count())->toBe(0)
        ->and(InterviewTurn::count())->toBe(0);
});
