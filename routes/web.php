<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InterviewSessionController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::get('interviews/create', [InterviewSessionController::class, 'create'])->name('interviews.create');
    Route::post('interviews', [InterviewSessionController::class, 'store'])->name('interviews.store');
    Route::get('interviews/{interviewSession}', [InterviewSessionController::class, 'show'])
        ->whereNumber('interviewSession')
        ->name('interviews.show');
});

require __DIR__.'/settings.php';
