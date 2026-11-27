<?php

use App\Enums\InterviewStatus;
use App\Exceptions\InvalidInterviewTransition;
use App\Models\InterviewSession;

test('a draft can only become ready', function () {
    expect(InterviewStatus::Draft->canTransitionTo(InterviewStatus::Ready))->toBeTrue()
        ->and(InterviewStatus::Draft->canTransitionTo(InterviewStatus::Asking))->toBeFalse()
        ->and(InterviewStatus::Draft->canTransitionTo(InterviewStatus::ReportReady))->toBeFalse();
});

test('a ready session can go back to draft', function () {
    expect(InterviewStatus::Ready->canTransitionTo(InterviewStatus::Draft))->toBeTrue();
});

test('finished sessions have no transitions yet', function () {
    foreach (InterviewStatus::cases() as $next) {
        expect(InterviewStatus::ReportReady->canTransitionTo($next))->toBeFalse();
    }
});

test('the model refuses a transition the lifecycle does not allow', function () {
    $session = new InterviewSession;

    $session->transitionTo(InterviewStatus::ReportReady);
})->throws(InvalidInterviewTransition::class);
