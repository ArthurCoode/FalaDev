<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InterviewSessionController;
use App\Http\Controllers\InterviewTurnController;
use App\Http\Controllers\PilotEventController;
use App\Http\Controllers\PilotMetricsController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::get('interviews', [InterviewSessionController::class, 'index'])->name('interviews.index');
    Route::get('interviews/create', [InterviewSessionController::class, 'create'])->name('interviews.create');
    Route::post('interviews', [InterviewSessionController::class, 'store'])->name('interviews.store');
    Route::get('interviews/{interviewSession}', [InterviewSessionController::class, 'show'])
        ->whereNumber('interviewSession')
        ->name('interviews.show');
    Route::delete('interviews/{interviewSession}', [InterviewSessionController::class, 'destroy'])
        ->whereNumber('interviewSession')
        ->name('interviews.destroy');
    Route::post('interviews/{interviewSession}/ready', [InterviewSessionController::class, 'markReady'])
        ->whereNumber('interviewSession')
        ->name('interviews.ready');
    Route::post('interviews/{interviewSession}/start', [InterviewSessionController::class, 'start'])
        ->whereNumber('interviewSession')
        ->name('interviews.start');
    Route::post('interviews/{interviewSession}/evaluation', [InterviewSessionController::class, 'retryEvaluation'])
        ->whereNumber('interviewSession')
        ->middleware('throttle:10,1')
        ->name('interviews.evaluation.retry');
    Route::post('interviews/{interviewSession}/evaluation/feedback', [InterviewSessionController::class, 'rateEvaluation'])
        ->whereNumber('interviewSession')
        ->middleware('throttle:10,1')
        ->name('interviews.evaluation.feedback');
    Route::post('interviews/{interviewSession}/finish', [InterviewSessionController::class, 'finish'])
        ->whereNumber('interviewSession')
        ->name('interviews.finish');

    // Pilot (FalaDev.md, section 12): technical events and the metrics page.
    Route::post('pilot/events', PilotEventController::class)
        ->middleware('throttle:30,1')
        ->name('pilot.events.store');
    Route::get('pilot/metrics', PilotMetricsController::class)->name('pilot.metrics');

    Route::prefix('interviews/{interviewSession}/turns/{turn}')
        ->whereNumber(['interviewSession', 'turn'])
        ->name('interviews.turns.')
        ->group(function () {
            // Each call goes to the STT provider, so it is rate limited.
            Route::post('transcribe', [InterviewTurnController::class, 'transcribe'])
                ->middleware('throttle:20,1')
                ->name('transcribe');
            Route::post('answer', [InterviewTurnController::class, 'answer'])->name('answer');
            Route::post('repeat', [InterviewTurnController::class, 'repeat'])->name('repeat');
        });
});

require __DIR__.'/settings.php';
