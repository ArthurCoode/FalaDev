<?php

namespace App\Http\Controllers;

use App\Models\InterviewSession;
use App\Services\InterviewUsage;
use App\Study\StudyTrack;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    private const RECENT_SESSIONS = 5;

    /**
     * Home screen: start a new interview, see recent ones and remaining usage,
     * and the study streak with what to study next.
     */
    public function __invoke(Request $request, InterviewUsage $usage, StudyTrack $study): Response
    {
        $user = $request->user();

        $recentSessions = $user->interviewSessions()
            ->latest()
            ->latest('id')
            ->limit(self::RECENT_SESSIONS)
            ->get()
            ->map(fn (InterviewSession $session) => $session->summary());

        $inProgress = $user->interviewInProgress();

        return Inertia::render('Dashboard', [
            // Shown as "Continuar entrevista" instead of "Nova entrevista".
            'inProgress' => $inProgress ? [
                'id' => $inProgress->id,
                'category' => $inProgress->category->label(),
                'level' => $inProgress->level->label(),
                'startedAt' => $inProgress->started_at?->toIso8601String(),
            ] : null,
            'recentSessions' => $recentSessions,
            'usage' => $usage->summary($user),
            'study' => [
                ...$study->stats($user),
                'nextLesson' => ($next = $study->nextLesson($study->completedLessons($user)))
                    ? ['slug' => $next->slug, 'title' => $next->title]
                    : null,
            ],
        ]);
    }
}
