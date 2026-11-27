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

test('report-ready sessions have no transitions yet', function () {
    foreach (InterviewStatus::cases() as $next) {
        expect(InterviewStatus::ReportReady->canTransitionTo($next))->toBeFalse();
    }
});

test('the model refuses a transition the lifecycle does not allow', function () {
    $session = new InterviewSession;

    $session->transitionTo(InterviewStatus::ReportReady);
})->throws(InvalidInterviewTransition::class);

test('a ready session can start, and a running one can only complete', function () {
    expect(InterviewStatus::Ready->canTransitionTo(InterviewStatus::Asking))->toBeTrue()
        ->and(InterviewStatus::Asking->canTransitionTo(InterviewStatus::Completed))->toBeTrue()
        ->and(InterviewStatus::Asking->canTransitionTo(InterviewStatus::Ready))->toBeFalse()
        ->and(InterviewStatus::Completed->canTransitionTo(InterviewStatus::Asking))->toBeFalse();
});

test('a completed interview goes through evaluation, and a failed one can be retried', function () {
    expect(InterviewStatus::Completed->canTransitionTo(InterviewStatus::Evaluating))->toBeTrue()
        ->and(InterviewStatus::Evaluating->canTransitionTo(InterviewStatus::ReportReady))->toBeTrue()
        ->and(InterviewStatus::Evaluating->canTransitionTo(InterviewStatus::FailedRetryable))->toBeTrue()
        ->and(InterviewStatus::FailedRetryable->canTransitionTo(InterviewStatus::Evaluating))->toBeTrue()
        ->and(InterviewStatus::Evaluating->canTransitionTo(InterviewStatus::Asking))->toBeFalse();
});
